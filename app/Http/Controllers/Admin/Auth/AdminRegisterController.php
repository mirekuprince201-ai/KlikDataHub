<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminRegisterController extends Controller
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

        if ((int) ($setting->allow_admin_registration ?? 1) !== 1) {
            return response()->view('admin.auth.disabled', [
                'title' => $setting->message_title ?? 'Admin Registration Disabled',
                'message' => $setting->message_body ?? 'Admin registration is currently disabled. Please contact the system owner.',
            ], 403);
        }

        return view('admin.auth.register', [
            'setting' => $setting,
            'registrationEnabled' => true,
            'loginEnabled' => (int) ($setting->allow_admin_login ?? 1) === 1,
        ]);
    }

    public function store(Request $request)
    {
        $setting = $this->authSetting();

        if ((int) ($setting->allow_admin_registration ?? 1) !== 1) {
            return response()->view('admin.auth.disabled', [
                'title' => $setting->message_title ?? 'Admin Registration Disabled',
                'message' => $setting->message_body ?? 'Admin registration is currently disabled. Please contact the system owner.',
            ], 403);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],
            'username'   => ['required', 'string', 'max:100', 'unique:admins,username'],
            'email'      => ['required', 'email', 'max:150', 'unique:admins,email'],
            'phone'      => ['required', 'string', 'max:30', 'unique:admins,phone'],
            'password'   => ['required', 'confirmed', 'min:8'],
        ]);

        Admin::create([
            'first_name' => strtoupper(trim($validated['first_name'])),
            'last_name'  => strtoupper(trim($validated['last_name'])),
            'other_name' => strtoupper(trim($validated['other_name'] ?? '')),
            'username'   => strtolower(trim($validated['username'])),
            'email'      => strtolower(trim($validated['email'])),
            'phone'      => trim($validated['phone']),
            'password'   => Hash::make($validated['password']),
            'status'     => 'Active',
        ]);

        return redirect()
            ->route('admin.auth.login')
            ->with('success', 'Admin account created successfully.');
    }
}