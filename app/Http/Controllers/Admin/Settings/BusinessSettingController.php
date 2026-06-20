<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BusinessSettingController extends Controller
{
    public function index()
    {
        $setting = $this->getOrCreateSettings();

        return view('admin.settings.business', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = $this->getOrCreateSettings();

        $validated = $request->validate([
            'business_name' => ['nullable', 'string', 'max:180'],
            'business_email' => ['nullable', 'email', 'max:180'],
            'business_phone' => ['nullable', 'string', 'max:50'],
            'business_address' => ['nullable', 'string', 'max:1000'],

            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],

            'announcement_title' => ['nullable', 'string', 'max:255'],
            'announcement_message' => ['nullable', 'string', 'max:3000'],
            'announcement_status' => ['required', 'in:active,inactive'],
            'announcement_template' => ['nullable', 'string', 'max:50'],
            'announcement_emoji' => ['nullable', 'string', 'max:20'],
            'announcement_button_text' => ['nullable', 'string', 'max:100'],

            'maintenance_title' => ['nullable', 'string', 'max:255'],
            'maintenance_message' => ['nullable', 'string', 'max:3000'],

            'developer_email' => ['nullable', 'email', 'max:180'],

            'support_whatsapp' => ['nullable', 'string', 'max:50'],
            'support_email' => ['nullable', 'email', 'max:180'],

            'payment_notice' => ['nullable', 'string', 'max:3000'],
        ]);

        $data = [
            'business_name' => $validated['business_name'] ?? null,
            'business_email' => $validated['business_email'] ?? null,
            'business_phone' => $validated['business_phone'] ?? null,
            'business_address' => $validated['business_address'] ?? null,

            'open_time' => !empty($validated['open_time'])
                ? $validated['open_time'] . ':00'
                : null,

            'close_time' => !empty($validated['close_time'])
                ? $validated['close_time'] . ':00'
                : null,

            'working_days' => !empty($validated['working_days'])
                ? implode(',', $validated['working_days'])
                : null,

            'announcement_title' => $validated['announcement_title'] ?? null,
            'announcement_message' => $validated['announcement_message'] ?? null,
            'announcement_status' => $validated['announcement_status'],

            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'maintenance_title' => $validated['maintenance_title'] ?? null,
            'maintenance_message' => $validated['maintenance_message'] ?? null,

            'developer_email' => $validated['developer_email'] ?? null,

            'allow_registration' => $request->boolean('allow_registration'),
            'allow_login' => $request->boolean('allow_login'),
            'registration_system_enabled' => $request->boolean('registration_system_enabled'),

            'payment_system_enabled' => $request->boolean('payment_system_enabled'),
            'paystack_enabled' => $request->boolean('paystack_enabled'),
            'payment_notice' => $validated['payment_notice'] ?? null,

            'support_whatsapp' => $validated['support_whatsapp'] ?? null,
            'support_email' => $validated['support_email'] ?? null,

            'updated_at' => now(),
        ];

        if (Schema::hasColumn('business_settings', 'announcement_template')) {
            $data['announcement_template'] = $validated['announcement_template'] ?? 'general';
        }

        if (Schema::hasColumn('business_settings', 'announcement_emoji')) {
            $data['announcement_emoji'] = $validated['announcement_emoji'] ?? '📢';
        }

        if (Schema::hasColumn('business_settings', 'announcement_button_text')) {
            $data['announcement_button_text'] = $validated['announcement_button_text'] ?? 'Okay, I Understand';
        }

        DB::table('business_settings')
            ->where('id', $setting->id)
            ->update($data);

        return back()->with('success', 'Business settings updated successfully.');
    }

public function toggle(Request $request)
{
    $setting = $this->getOrCreateSettings();

    $validated = $request->validate([
        'field' => [
            'required',
            'string',
            'in:announcement_status,maintenance_mode,allow_registration,allow_login,registration_system_enabled,payment_system_enabled,paystack_enabled',
        ],
        'value' => ['required'],
    ]);

    $field = $validated['field'];

    if ($field === 'announcement_status') {
        $value = $validated['value'] === 'active' ? 'active' : 'inactive';
    } else {
        $value = filter_var($validated['value'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    DB::table('business_settings')
        ->where('id', $setting->id)
        ->update([
            $field => $value,
            'updated_at' => now(),
        ]);

    return response()->json([
        'success' => true,
        'message' => 'Setting updated successfully.',
        'field' => $field,
        'value' => $value,
    ]);
}

    private function getOrCreateSettings()
    {
        $setting = DB::table('business_settings')->first();

        if ($setting) {
            return $setting;
        }

        $data = [
            'business_name' => 'KLIKDATA',
            'business_email' => 'support@klikdata.com',
            'business_phone' => '0240000000',
            'business_address' => 'Accra, Ghana',

            'open_time' => '08:00:00',
            'close_time' => '18:00:00',
            'working_days' => 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',

            'announcement_title' => 'Welcome',
            'announcement_message' => 'Welcome to our platform.',
            'announcement_status' => 'inactive',

            'maintenance_mode' => 0,
            'maintenance_title' => 'Maintenance Mode',
            'maintenance_message' => 'We are currently updating the system. Please check back later.',

            'developer_email' => 'developer@klikdatahub.com',

            'allow_registration' => 1,
            'allow_login' => 1,
            'registration_system_enabled' => 1,

            'payment_system_enabled' => 1,
            'paystack_enabled' => 1,
            'payment_notice' => 'Payments are temporarily unavailable. Please try again later.',

            'support_whatsapp' => '0240000000',
            'support_email' => 'support@klikdata.com',

            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('business_settings', 'announcement_template')) {
            $data['announcement_template'] = 'general';
        }

        if (Schema::hasColumn('business_settings', 'announcement_emoji')) {
            $data['announcement_emoji'] = '📢';
        }

        if (Schema::hasColumn('business_settings', 'announcement_button_text')) {
            $data['announcement_button_text'] = 'Okay, I Understand';
        }

        DB::table('business_settings')->insert($data);

        return DB::table('business_settings')->first();
    }
}