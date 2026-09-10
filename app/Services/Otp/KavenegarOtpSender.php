<?php
namespace App\Services\Otp;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class KavenegarOtpSender implements OtpSender {
 public function send(string $mobile,string $code,string $template): ?string {
  $key=config('services.kavenegar.api_key'); if(!$key) throw new RuntimeException('Kavenegar API key is not configured.');
  $r=Http::timeout(10)->get("https://api.kavenegar.com/v1/{$key}/verify/lookup.json",['receptor'=>$mobile,'token'=>$code,'template'=>$template]);
  $r->throw(); return data_get($r->json(),'entries.0.messageid');
 }
}
