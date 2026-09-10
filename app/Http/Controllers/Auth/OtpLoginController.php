<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Services\Otp\KavenegarException;
use App\Services\Otp\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OtpLoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['mobile' => $data['mobile'], 'password' => $data['password'], 'is_active' => true], true)) {
            throw ValidationException::withMessages(['mobile' => 'شماره موبایل یا رمز عبور صحیح نیست.']);
        }

        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function send(Request $request, OtpService $otp)
    {
        $data = $request->validate(['mobile' => ['required', 'regex:/^09\d{9}$/']]);
        $user = User::where('mobile', $data['mobile'])->where('is_active', true)->first();

        if (! $user) {
            return back()->withErrors(['mobile' => 'کاربر فعالی با این شماره یافت نشد.'])->withInput();
        }

        try {
            $challenge = $otp->issue($user, 'login');
        } catch (KavenegarException $exception) {
            report($exception);
            return back()->withErrors(['mobile' => $exception->getMessage()])->withInput();
        }

        session(['login_challenge_id' => $challenge->id]);

        return redirect()->route('login.verify')->with('status', 'کد ورود ارسال شد.');
    }

    public function verifyForm()
    {
        abort_unless(session('login_challenge_id'), 404);

        return view('auth.verify');
    }

    public function verify(Request $request, OtpService $otp)
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $challenge = OtpChallenge::findOrFail(session('login_challenge_id'));
        $otp->verify($challenge, $data['code']);
        $user = $challenge->user()->firstOrFail();

        Auth::login($user, true);
        $user->update(['mobile_verified_at' => $user->mobile_verified_at ?? now(), 'last_login_at' => now()]);
        $request->session()->regenerate();
        $request->session()->forget('login_challenge_id');

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
