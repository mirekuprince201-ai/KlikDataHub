<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutoCompleteSettingController extends Controller
{
    public function index()
    {
        $setting = DB::table('auto_complete_settings')
            ->where('setting_key', 'progress_orders_auto_complete')
            ->first();

        if (!$setting) {
            DB::table('auto_complete_settings')->insert([
                'setting_key' => 'progress_orders_auto_complete',
                'timer_minutes' => 30,
                'is_enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $setting = DB::table('auto_complete_settings')
                ->where('setting_key', 'progress_orders_auto_complete')
                ->first();
        }

        return view('admin.settings.autocomplete.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'timer_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        DB::table('auto_complete_settings')->updateOrInsert(
            ['setting_key' => 'progress_orders_auto_complete'],
            [
                'timer_minutes' => (int) $request->timer_minutes,
                'is_enabled' => $request->boolean('is_enabled') ? 1 : 0,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Auto complete settings updated successfully.');
    }
}