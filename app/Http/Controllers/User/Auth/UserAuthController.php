<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Throwable;

class UserAuthController extends Controller
{
    private function business()
    {
        return DB::table('business_settings')->first();
    }

    private function registrationEnabled(): bool
    {
        $business = $this->business();

        return (int) ($business->registration_system_enabled ?? $business->allow_registration ?? 1) === 1;
    }

    private function loginEnabled(): bool
    {
        $business = $this->business();

        return (int) ($business->allow_login ?? 1) === 1;
    }

    private function invalidLogin(Request $request)
    {
        return back()
            ->withErrors(['login' => 'Invalid email, phone number, or password.'])
            ->onlyInput('login');
    }

    private function verifyPassword(User $user, string $plainPassword): bool
    {
        $storedPassword = trim((string) ($user->password ?? ''));

        if ($storedPassword === '') {
            return false;
        }

        try {
            if (Hash::check($plainPassword, $storedPassword)) {
                if (Hash::needsRehash($storedPassword)) {
                    $user->forceFill([
                        'password' => Hash::make($plainPassword),
                    ])->save();
                }

                return true;
            }
        } catch (RuntimeException|Throwable $e) {
            // Invalid/non-bcrypt password hash. Continue safely below.
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy plain-text password support
        |--------------------------------------------------------------------------
        | This prevents a 500 error when old users have plain passwords in DB.
        | If matched, it immediately converts the password to bcrypt.
        */
        if (hash_equals($storedPassword, $plainPassword)) {
            $user->forceFill([
                'password' => Hash::make($plainPassword),
            ])->save();

            return true;
        }

        return false;
    }

    public function showLogin()
    {
        if (Auth::guard('user')->check()) {
            return redirect('/dashboard');
        }

        if (! $this->loginEnabled()) {
            return response()->view('users.auth.unavailable', [
                'business' => $this->business(),
                'title' => 'Login Unavailable',
                'message' => 'Login is currently disabled. Please contact support for assistance.',
            ], 503);
        }

        return view('users.auth.login', [
            'business' => $this->business(),
            'registrationEnabled' => $this->registrationEnabled(),
        ]);
    }

    public function showRegister()
    {
        if (Auth::guard('user')->check()) {
            return redirect('/dashboard');
        }

        if (! $this->registrationEnabled()) {
            return response()->view('users.auth.unavailable', [
                'business' => $this->business(),
                'title' => 'Registration Unavailable',
                'message' => 'New account registration is currently disabled. Please contact support for assistance.',
            ], 503);
        }

        return view('users.auth.register', [
            'business' => $this->business(),
            'registrationEnabled' => true,
            'loginEnabled' => $this->loginEnabled(),
        ]);
    }

    public function register(Request $request)
    {
        if (! $this->registrationEnabled()) {
            return response()->view('users.auth.unavailable', [
                'business' => $this->business(),
                'title' => 'Registration Unavailable',
                'message' => 'New account registration is currently disabled. Please contact support for assistance.',
            ], 503);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'phone'      => ['required', 'digits:10', 'unique:users,phone'],
            'email'      => ['required', 'email', 'max:150', 'unique:users,email'],
            'password'   => ['required', 'confirmed', Password::min(8)],
        ]);

        $firstName = strtoupper(trim($validated['first_name']));
        $lastName = strtoupper(trim($validated['last_name']));
        $fullName = trim($firstName . ' ' . $lastName);

        $user = User::create([
            'user_id'        => (string) Str::uuid(),
            'first_name'     => $firstName,
            'last_name'      => $lastName,
            'full_name'      => $fullName,
            'phone'          => trim($validated['phone']),
            'email'          => strtolower(trim($validated['email'])),
            'password'       => Hash::make($validated['password']),
            'wallet_balance' => 0.00,
            'account_status' => 'active',
            'last_login_at'  => now(),
            'last_login_ip'  => $request->ip(),
        ]);

        Auth::guard('user')->login($user);

        $request->session()->regenerate();

        return redirect('/dashboard')->with('success', 'Account created successfully.');
    }

    public function login(Request $request)
    {
        if (! $this->loginEnabled()) {
            return response()->view('users.auth.unavailable', [
                'business' => $this->business(),
                'title' => 'Login Unavailable',
                'message' => 'Login is currently disabled. Please contact support for assistance.',
            ], 503);
        }

        $validated = $request->validate([
            'login'    => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
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
            return $this->invalidLogin($request);
        }

        if (! $this->verifyPassword($user, $validated['password'])) {
            return $this->invalidLogin($request);
        }

        if (($user->account_status ?? 'active') !== 'active') {
            return back()
                ->withErrors(['login' => 'Your account is not active. Please contact support.'])
                ->onlyInput('login');
        }

        Auth::guard('user')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect('/dashboard')->with('success', 'Logged in successfully.');
    }

    public function logout(Request $request)
    {
        Auth::guard('user')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully.');
    }
}