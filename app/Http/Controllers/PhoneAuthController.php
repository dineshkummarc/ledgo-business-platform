<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Auth\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PhoneAuthController extends Controller
{
    public function show()
    {
        return view('auth.phone');
    }

    public function send(Request $request, OtpService $otp)
    {
        $data = $request->validate(['phone' => ['required', 'regex:/^8801[3-9][0-9]{8}$/']]);
        RateLimiter::attempt('otp-send:'.$data['phone'], 1, fn () => $otp->send($data['phone']), 60) === false && abort(429);

        return back()->with('phone', $data['phone'])->with('status', __('ledgo.code_sent'));
    }

    public function verify(Request $request, OtpService $otp)
    {
        $data = $request->validate(['phone' => ['required', 'regex:/^8801[3-9][0-9]{8}$/'], 'code' => 'required|digits:6']);
        $otp->verify($data['phone'], $data['code']);
        $user = User::firstOrCreate(['phone' => $data['phone']], ['name' => $data['phone'], 'email' => null, 'password' => Str::random(64)]);
        $user->forceFill(['phone_verified_at' => now()])->save();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect($user->businesses()->exists() ? '/app' : '/onboarding');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
