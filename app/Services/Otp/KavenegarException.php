<?php
namespace App\Services\Otp;

use RuntimeException;
use Throwable;

class KavenegarException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $providerStatus = null, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function fromStatus(int $status, ?string $providerMessage = null): self
    {
        $message = match ($status) {
            400 => 'اطلاعات ارسالی به سرویس پیامک ناقص است.',
            401 => 'حساب کاوه‌نگار غیرفعال است. با پشتیبانی یا مدیر سیستم تماس بگیرید.',
            403 => 'کلید API کاوه‌نگار معتبر نیست. تنظیمات پیامک باید بررسی شود.',
            409 => 'سرویس کاوه‌نگار موقتاً قادر به پاسخ‌گویی نیست. کمی بعد دوباره تلاش کنید.',
            418 => 'اعتبار پنل پیامک کاوه‌نگار کافی نیست. لطفاً پنل پیامک را شارژ کنید.',
            422 => 'اطلاعات OTP برای کاوه‌نگار قابل پردازش نیست.',
            424 => 'الگوی OTP در کاوه‌نگار پیدا نشد یا هنوز تأیید نشده است.',
            426 => 'سرویس اعتبارسنجی کاوه‌نگار برای این حساب فعال نیست.',
            431, 432 => 'ساختار الگوی OTP کاوه‌نگار صحیح نیست و باید اصلاح شود.',
            default => $providerMessage
                ? "ارسال پیامک با خطای کاوه‌نگار مواجه شد: {$providerMessage}"
                : 'ارسال پیامک با خطای سرویس کاوه‌نگار مواجه شد.',
        };

        return new self($message, $status);
    }

    public static function connectionFailed(?Throwable $previous = null): self
    {
        return new self('اتصال به سرویس پیامک کاوه‌نگار برقرار نشد. شبکه یا وضعیت سرویس را بررسی کنید.', null, $previous);
    }
}
