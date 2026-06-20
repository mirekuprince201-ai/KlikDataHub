<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminAuthSettingController extends Controller
{
    private function setting()
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

    public function index()
    {
        $setting = $this->setting();

        return view('admin.settings.admin-auth-settings', compact('setting'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'message_title' => ['nullable', 'string', 'max:150'],
            'message_body' => ['nullable', 'string', 'max:1000'],
        ]);

        $setting = $this->setting();

        DB::table('admin_auth_settings')
            ->where('id', $setting->id)
            ->update([
                'allow_admin_login' => $request->has('allow_admin_login') ? 1 : 0,
                'allow_admin_registration' => $request->has('allow_admin_registration') ? 1 : 0,
                'message_title' => $validated['message_title'] ?? 'Admin Access Unavailable',
                'message_body' => $validated['message_body'] ?? 'Admin access is currently disabled. Please contact the system owner.',
                'updated_by' => Auth::guard('admin')->id(),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Admin authentication settings updated successfully.');
    }
}