<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminLoginController extends Controller
{
    private function authSetting()
    {
        $setting = DB::table('admin_auth_settings')->first();

        if (! $setting) {
            DB::table('admin_auth_settings')->insert([
                'allow_admin_login' => 1,
                'allow_admin_registration' => 1,
                'message_title' => 'Admin Access Unavailable',
                'message_body' => 'Admin access is currently disabled. Please contact the system owner.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $setting = DB::table('admin_auth_settings')->first();
        }

        return $setting;
    }

    public function create()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        $setting = $this->authSetting();

        if ((int) ($setting->allow_admin_login ?? 1) !== 1) {
            return response()->view('admin.auth.disabled', [
                'title' => $setting->message_title ?? 'Admin Access Disabled',
                'message' => $setting->message_body ?? 'Admin access is currently disabled. Please contact the system owner.',
            ], 403);
        }

        return view('admin.auth.login', [
            'setting' => $setting,
            'loginEnabled' => true,
            'registrationEnabled' => (int) ($setting->allow_admin_registration ?? 1) === 1,
        ]);
    }

    public function store(Request $request)
    {
        $setting = $this->authSetting();

        if ((int) ($setting->allow_admin_login ?? 1) !== 1) {
            return response()->view('admin.auth.disabled', [
                'title' => $setting->message_title ?? 'Admin Access Disabled',
                'message' => $setting->message_body ?? 'Admin access is currently disabled. Please contact the system owner.',
            ], 403);
        }

        $validated = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($validated['login']);

        $admin = Admin::where(function ($query) use ($login) {
            $query->where('email', strtolower($login))
                ->orWhere('username', $login);
        })->first();

        if (! $admin) {
            return back()
                ->withErrors([
                    'login' => 'Invalid email, username, or password.',
                ])
                ->onlyInput('login');
        }

        if (($admin->status ?? 'Inactive') !== 'Active') {
            return back()
                ->withErrors([
                    'login' => 'Your admin account is not active.',
                ])
                ->onlyInput('login');
        }

        if (
            $admin->locked_until &&
            Carbon::parse($admin->locked_until)->isFuture()
        ) {
            return back()
                ->withErrors([
                    'login' => 'Your account is locked. Try again later.',
                ])
                ->onlyInput('login');
        }

        if (! Hash::check($validated['password'], $admin->password)) {
            $admin->failed_login_attempts = ((int) $admin->failed_login_attempts) + 1;

            if ($admin->failed_login_attempts >= 5) {
                $admin->locked_until = now()->addMinutes(15);
            }

            $admin->save();

            return back()
                ->withErrors([
                    'login' => 'Invalid email, username, or password.',
                ])
                ->onlyInput('login');
        }

        $admin->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        Auth::guard('admin')->login(
            $admin,
            $request->boolean('remember')
        );

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.auth.login')
            ->with('success', 'You have logged out successfully.');
    }
}