<?php
namespace App\Services\Otp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class KavenegarOtpSender implements OtpSender
{
    public function send(string $mobile, string $code, string $template): ?string
    {
        $key = trim((string) config('services.kavenegar.api_key'));
        if ($key === '') {
            throw new KavenegarException('کلید API کاوه‌نگار تنظیم نشده است.');
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout((int) config('services.kavenegar.timeout_seconds', 8))
                ->get("https://api.kavenegar.com/v1/{$key}/verify/lookup.json", [
                    'receptor' => $mobile,
                    'token' => $code,
                    'template' => $template,
                ]);
        } catch (ConnectionException $exception) {
            throw KavenegarException::connectionFailed($exception);
        }

        $payload = $response->json();
        $status = (int) data_get($payload, 'return.status', $response->status());

        if (! $response->successful() || $status !== 200) {
            throw KavenegarException::fromStatus($status, data_get($payload, 'return.message'));
        }

        $messageId = data_get($payload, 'entries.0.messageid');
        if (! $messageId) {
            throw new KavenegarException('پاسخ سرویس کاوه‌نگار کامل نبود و شناسه پیام دریافت نشد.');
        }

        return (string) $messageId;
    }
}
