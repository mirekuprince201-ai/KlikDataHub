<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckBusinessMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        $settings = DB::table('business_settings')->first();

        if (!$settings || !$settings->maintenance_mode) {
            return $next($request);
        }

        $developerKey = config('app.developer_maintenance_key');

        if (
            $developerKey &&
            $request->query('developer_key') === $developerKey
        ) {
            session(['developer_maintenance_access' => true]);

            return $next($request);
        }

        if (session('developer_maintenance_access') === true) {
            return $next($request);
        }

        if ($request->is('admin/settings/business') || $request->is('admin/logout')) {
            return $next($request);
        }

        return response()->view('maintenance.business', [
            'title' => $settings->maintenance_title ?? 'Maintenance Mode',
            'message' => $settings->maintenance_message ?? 'We are currently updating the system. Please check back later.',
        ], 503);
    }
}