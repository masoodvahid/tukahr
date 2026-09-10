<?php
namespace Tests\Feature;

use App\Services\Otp\KavenegarException;
use App\Services\Otp\KavenegarOtpSender;
use App\Services\Otp\KavenegarStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KavenegarStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_reports_zero_credit(): void
    {
        config(['services.kavenegar.api_key' => 'dummy-key']);
        Cache::forget('kavenegar.account-status');
        Http::fake(['api.kavenegar.com/*' => Http::response([
            'return' => ['status' => 200, 'message' => 'ok'],
            'entries' => ['remaincredit' => 0],
        ], 200)]);

        $status = app(KavenegarStatusService::class)->status();

        $this->assertSame('error', $status['severity']);
        $this->assertSame(0, $status['credit']);
    }

    public function test_sender_translates_insufficient_credit_error(): void
    {
        config(['services.kavenegar.api_key' => 'dummy-key']);
        Http::fake(['api.kavenegar.com/*' => Http::response([
            'return' => ['status' => 418, 'message' => 'low credit'],
        ], 418)]);

        $this->expectException(KavenegarException::class);
        $this->expectExceptionMessage('اعتبار پنل پیامک کاوه‌نگار کافی نیست');

        app(KavenegarOtpSender::class)->send('test-recipient', '123456', 'test-template');
    }
}
