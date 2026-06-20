<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()
                ->route('admin.auth.login')
                ->with('error', 'Please login as admin first.');
        }

        if (Auth::guard('admin')->user()->status !== 'Active') {
            Auth::guard('admin')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.auth.login')
                ->with('error', 'Your admin account is not active.');
        }

        return $next($request);
    }
}