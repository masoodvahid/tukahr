<?php
namespace App\Services\Otp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class KavenegarStatusService
{
    public function status(): array
    {
        return Cache::remember('kavenegar.account-status', now()->addMinutes(2), fn () => $this->fetchStatus());
    }

    private function fetchStatus(): array
    {
        $key = trim((string) config('services.kavenegar.api_key'));
        if ($key === '') {
            return [
                'severity' => 'warning',
                'configured' => false,
                'credit' => null,
                'message' => 'کلید API کاوه‌نگار هنوز تنظیم نشده است.',
            ];
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout((int) config('services.kavenegar.timeout_seconds', 8))
                ->get("https://api.kavenegar.com/v1/{$key}/account/info.json");

            $payload = $response->json();
            $status = (int) data_get($payload, 'return.status', $response->status());

            if (! $response->successful() || $status !== 200) {
                $exception = KavenegarException::fromStatus($status, data_get($payload, 'return.message'));

                return [
                    'severity' => 'error',
                    'configured' => true,
                    'credit' => null,
                    'message' => $exception->getMessage(),
                ];
            }

            $credit = (int) data_get($payload, 'entries.remaincredit', 0);
            $threshold = (int) config('services.kavenegar.low_credit_threshold', 100000);

            if ($credit <= 0) {
                $severity = 'error';
                $message = 'اعتبار پنل پیامک تمام شده است و ارسال OTP ممکن نیست.';
            } elseif ($credit <= $threshold) {
                $severity = 'warning';
                $message = 'اعتبار پنل پیامک رو به اتمام است.';
            } else {
                $severity = 'ok';
                $message = 'سرویس پیامک در دسترس است.';
            }

            return [
                'severity' => $severity,
                'configured' => true,
                'credit' => $credit,
                'message' => $message,
            ];
        } catch (ConnectionException $exception) {
            report($exception);

            return [
                'severity' => 'error',
                'configured' => true,
                'credit' => null,
                'message' => 'ارتباط با کاوه‌نگار برقرار نشد. اتصال شبکه یا وضعیت سرویس را بررسی کنید.',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'severity' => 'error',
                'configured' => true,
                'credit' => null,
                'message' => 'در بررسی وضعیت پنل پیامک خطای غیرمنتظره‌ای رخ داد. ورود با رمز عبور همچنان قابل استفاده است.',
            ];
        }
    }
}
