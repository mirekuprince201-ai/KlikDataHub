<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

class ForgotPasswordController extends Controller
{
    private function business()
    {
        return DB::table('business_settings')->first();
    }

    private function businessName(): string
    {
        $business = $this->business();

        return $business->business_name ?? 'KlikDataHub';
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '233' . substr($phone, 1);
        }

        if (str_starts_with($phone, '233') && strlen($phone) === 12) {
            return $phone;
        }

        return $phone;
    }

    private function sendSms(string $phone, string $message): bool
    {
        $baseUrl = rtrim((string) config('services.smsnotify.base', env('SMSNOTIFY_API_BASE')), '/');
        $apiKey = config('services.smsnotify.key', env('SMSNOTIFY_API_KEY'));
        $sender = config('services.smsnotify.sender', env('SMS_SENDER_ID', 'KLIKDATAHUB'));
        $fakeSms = filter_var(config('services.smsnotify.fake', env('FAKE_SMS', false)), FILTER_VALIDATE_BOOLEAN);

        if ($fakeSms) {
            logger()->info('FAKE PASSWORD RESET SMS', [
                'phone' => $phone,
                'message' => $message,
            ]);

            return true;
        }

        if (empty($baseUrl) || empty($apiKey) || empty($sender)) {
            logger()->error('SMSNotify settings missing.');

            return false;
        }

        try {
            $response = Http::timeout(25)->get($baseUrl, [
                'key' => $apiKey,
                'to' => $phone,
                'msg' => $message,
                'sender_id' => $sender,
            ]);

            logger()->info('Password reset SMS response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $response->successful();
        } catch (Throwable $e) {
            logger()->error('Password reset SMS failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function showForgotPassword()
    {
        return view('users.auth.forgot-password', [
            'business' => $this->business(),
        ]);
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:150'],
        ], [
            'login.required' => 'Please enter your email address or phone number.',
        ]);

        $login = trim($validated['login']);
        $phone = preg_replace('/[^0-9]/', '', $login);

        $user = User::where(function ($query) use ($login, $phone) {
            $query->where('email', strtolower($login));

            if (! empty($phone)) {
                $query->orWhere('phone', $phone);
            }
        })->first();

        if (! $user) {
            return back()
                ->withErrors(['login' => 'No account was found with this email address or phone number.'])
                ->onlyInput('login');
        }

        if (($user->account_status ?? 'active') !== 'active') {
            return back()
                ->withErrors(['login' => 'Your account is not active. Please contact support.'])
                ->onlyInput('login');
        }

        if (empty($user->phone)) {
            return back()
                ->withErrors(['login' => 'This account does not have a phone number for OTP verification.'])
                ->onlyInput('login');
        }

        $otp = (string) random_int(100000, 999999);

        session([
            'password_reset_user_id' => $user->user_id,
            'password_reset_otp' => Hash::make($otp),
            'password_reset_otp_expires_at' => now()->addMinutes(10)->timestamp,
            'password_reset_verified' => false,
        ]);

        $phoneToSend = $this->normalizePhone($user->phone);
        $businessName = $this->businessName();
        $fullName = $user->full_name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        $message = 'Dear ' . $fullName . ', your ' . $businessName . ' password reset OTP is ' . $otp . '. It expires in 10 minutes. Do not share it.';

        $sent = $this->sendSms($phoneToSend, $message);

        if (! $sent) {
            return back()
                ->withErrors(['login' => 'We could not send the OTP now. Please try again later.'])
                ->onlyInput('login');
        }

        return redirect('/verify-reset-otp')
            ->with('success', 'OTP sent successfully to your phone number.');
    }

    public function showVerifyOtp()
    {
        if (! session('password_reset_user_id')) {
            return redirect('/forgot-password');
        }

        return view('users.auth.verify-reset-otp', [
            'business' => $this->business(),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Please enter the OTP sent to your phone.',
            'otp.digits' => 'OTP must be 6 digits.',
        ]);

        if (! session('password_reset_user_id')) {
            return redirect('/forgot-password');
        }

        if (now()->timestamp > (int) session('password_reset_otp_expires_at')) {
            session()->forget([
                'password_reset_user_id',
                'password_reset_otp',
                'password_reset_otp_expires_at',
                'password_reset_verified',
            ]);

            return redirect('/forgot-password')
                ->withErrors(['login' => 'Your OTP has expired. Please request a new one.']);
        }

        if (! Hash::check($validated['otp'], session('password_reset_otp'))) {
            return back()
                ->withErrors(['otp' => 'Invalid OTP. Please check and try again.']);
        }

        session([
            'password_reset_verified' => true,
        ]);

        return redirect('/reset-password')
            ->with('success', 'OTP verified successfully. Please set a new password.');
    }

    public function showResetPassword()
    {
        if (! session('password_reset_user_id') || session('password_reset_verified') !== true) {
            return redirect('/forgot-password');
        }

        return view('users.auth.reset-password', [
            'business' => $this->business(),
        ]);
    }

    public function resetPassword(Request $request)
    {
        if (! session('password_reset_user_id') || session('password_reset_verified') !== true) {
            return redirect('/forgot-password');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::where('user_id', session('password_reset_user_id'))->first();

        if (! $user) {
            return redirect('/forgot-password')
                ->withErrors(['login' => 'Account not found. Please try again.']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        session()->forget([
            'password_reset_user_id',
            'password_reset_otp',
            'password_reset_otp_expires_at',
            'password_reset_verified',
        ]);

        return redirect('/login')
            ->with('success', 'Password reset successfully. You can now login.');
    }
}