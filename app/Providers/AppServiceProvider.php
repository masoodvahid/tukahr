<?php
namespace App\Providers;
use App\Services\Otp\KavenegarOtpSender;
use App\Services\Otp\OtpSender;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider { public function register(): void {$this->app->bind(OtpSender::class,KavenegarOtpSender::class);} public function boot(): void {} }
