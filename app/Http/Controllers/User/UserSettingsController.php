<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserSettingsController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('user')->user();

        return view('users.settings.index', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('user')->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($user->user_id, 'user_id'),
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
            ],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $firstName = strtoupper(trim($validated['first_name']));
        $lastName = strtoupper(trim($validated['last_name']));
        $phone = preg_replace('/\D+/', '', $validated['phone']);
        $email = strtolower(trim($validated['email']));

        if (!preg_match('/^0\d{9}$/', $phone)) {
            return back()
                ->withInput()
                ->with('error', 'Phone number must be exactly 10 digits and start with 0.');
        }

        $profileImage = $user->profile_image;

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = 'user-' . $user->user_id . '-' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);

            $profileImage = 'uploads/users/' . $filename;
        }

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => trim($firstName . ' ' . $lastName),
                'phone' => $phone,
                'email' => $email,
                'profile_image' => $profileImage,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::guard('user')->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $freshUser = DB::table('users')
            ->where('user_id', $user->user_id)
            ->first();

        if (!$freshUser || !Hash::check($request->current_password, $freshUser->password)) {
            return back()
                ->withInput()
                ->with('error', 'Current password is incorrect.');
        }

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'password' => Hash::make($request->password),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Password updated successfully.');
    }
}