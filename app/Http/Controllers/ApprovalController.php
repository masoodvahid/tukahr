<?php
namespace App\Http\Controllers;

use App\Enums\ApprovalStage;
use App\Models\OtpChallenge;
use App\Models\PayrollSheet;
use App\Services\Otp\KavenegarException;
use App\Services\Payroll\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApprovalController extends Controller
{
    public function requestOtp(Request $request, PayrollSheet $sheet, ApprovalService $service)
    {
        $data = $request->validate([
            'stage' => ['required', Rule::enum(ApprovalStage::class)],
            'comment' => 'nullable|string|max:4000',
            'override' => 'nullable|boolean',
        ]);

        $stage = ApprovalStage::from($data['stage']);

        try {
            $challenge = $service->request(
                $request->user(),
                $sheet,
                $stage,
                $data['comment'] ?? null,
                (bool) ($data['override'] ?? false)
            );
        } catch (KavenegarException $exception) {
            report($exception);
            return back()->withErrors(['otp' => $exception->getMessage()]);
        }

        session(['approval_context' => [
            'challenge_id' => $challenge->id,
            'stage' => $stage->value,
            'comment' => $data['comment'] ?? null,
            'override' => (bool) ($data['override'] ?? false),
        ]]);

        return back()->with('status', 'کد تأیید ارسال شد.')->with('otp_pending', true);
    }

    public function confirm(Request $request, PayrollSheet $sheet, ApprovalService $service)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $context = session('approval_context');
        abort_unless($context && $context['challenge_id'], 422);

        $challenge = OtpChallenge::findOrFail($context['challenge_id']);
        abort_unless((int) $challenge->sheet_id === (int) $sheet->id, 422);

        $service->confirm(
            $request->user(),
            $challenge,
            $data['code'],
            ApprovalStage::from($context['stage']),
            $context['comment'],
            $context['override']
        );

        session()->forget('approval_context');

        return back()->with('status', 'تأیید با موفقیت ثبت شد.');
    }
}
