<?php
namespace App\Services\Otp;

use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class OtpService
{
    public function __construct(private OtpSender $sender) {}

    public function issue(User $user, string $purpose, array $context = []): OtpChallenge
    {
        $recent = OtpChallenge::where('user_id', $user->id)->where('purpose', $purpose)->latest()->first();
        if ($recent && $recent->created_at->diffInSeconds(now()) < config('payroll.otp_resend_seconds')) {
            throw ValidationException::withMessages(['mobile' => 'برای ارسال مجدد کمی صبر کنید.']);
        }

        OtpChallenge::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);

        $code = (string) random_int(100000, 999999);
        $challenge = OtpChallenge::create(array_merge([
            'user_id' => $user->id,
            'mobile_snapshot' => $user->mobile,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds(config('payroll.otp_ttl_seconds')),
        ], $context));

        $template = $purpose === 'login'
            ? config('services.kavenegar.otp_template')
            : config('services.kavenegar.approval_template');

        try {
            $messageId = $this->sender->send($user->mobile, $code, $template);
        } catch (Throwable $exception) {
            $challenge->update([
                'delivery_status' => 'failed',
                'invalidated_at' => now(),
            ]);
            throw $exception;
        }

        $challenge->update([
            'provider_message_id' => $messageId,
            'delivery_status' => 'sent',
        ]);

        return $challenge;
    }

    public function verify(OtpChallenge $challenge, string $code): void
    {
        if ($challenge->consumed_at || $challenge->invalidated_at || now()->greaterThan($challenge->expires_at)) {
            throw ValidationException::withMessages(['code' => 'کد منقضی یا استفاده شده است.']);
        }

        $challenge->increment('attempt_count');
        if ($challenge->attempt_count > config('payroll.otp_max_attempts')) {
            $challenge->update(['invalidated_at' => now()]);
            throw ValidationException::withMessages(['code' => 'تعداد تلاش بیش از حد مجاز است.']);
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            throw ValidationException::withMessages(['code' => 'کد واردشده صحیح نیست.']);
        }

        $challenge->update(['consumed_at' => now()]);
    }
}
