<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class OtpLoginController extends Controller {
 public function create(){return view('auth.login');}
 public function send(Request $r,OtpService $otp){$d=$r->validate(['mobile'=>['required','regex:/^09\d{9}$/']]);$u=User::where('mobile',$d['mobile'])->where('is_active',true)->first();if(!$u)return back()->withErrors(['mobile'=>'کاربر فعالی با این شماره یافت نشد.']);$c=$otp->issue($u,'login');session(['login_challenge_id'=>$c->id]);return redirect()->route('login.verify')->with('status','کد ورود ارسال شد.');}
 public function verifyForm(){abort_unless(session('login_challenge_id'),404);return view('auth.verify');}
 public function verify(Request $r,OtpService $otp){$d=$r->validate(['code'=>['required','digits:6']]);$c=OtpChallenge::findOrFail(session('login_challenge_id'));$otp->verify($c,$d['code']);$u=$c->user()->firstOrFail();Auth::login($u,true);$u->update(['mobile_verified_at'=>$u->mobile_verified_at??now(),'last_login_at'=>now()]);$r->session()->regenerate();$r->session()->forget('login_challenge_id');return redirect()->route('dashboard');}
 public function destroy(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('login');}
}
