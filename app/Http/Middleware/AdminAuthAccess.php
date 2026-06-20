<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthAccess
{
    public function handle(Request $request, Closure $next, string $type): Response
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

        if ($type === 'login' && (int) ($setting->allow_admin_login ?? 1) !== 1) {
            return redirect()
                ->route('admin.auth.login')
                ->with('error', $setting->message_body ?? 'Admin login is currently disabled.');
        }

        if ($type === 'register' && (int) ($setting->allow_admin_registration ?? 1) !== 1) {
            return redirect()
                ->route('admin.auth.register')
                ->with('error', $setting->message_body ?? 'Admin registration is currently disabled.');
        }

        return $next($request);
    }
}