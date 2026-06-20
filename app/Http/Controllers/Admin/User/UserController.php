<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = DB::table('users')
            ->select([
                'user_id',
                'first_name',
                'last_name',
                'full_name',
                'phone',
                'email',
                'wallet_balance',
                'profile_image',
                'account_status',
                'last_login_at',
                'last_login_ip',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'totalUsers' => $users->count(),
            'activeUsers' => $users->where('account_status', 'active')->count(),
            'inactiveUsers' => $users->where('account_status', 'inactive')->count(),
            'totalWallet' => $users->sum(fn ($user) => (float) ($user->wallet_balance ?? 0)),
        ]);
    }

    public function show(string $user_id)
    {
        $user = DB::table('users')->where('user_id', $user_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $fullName = strtoupper($user->full_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));

        return response()->json([
            'success' => true,
            'user' => [
                'user_id' => $user->user_id,
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'full_name' => $fullName ?: 'N/A',
                'phone' => $user->phone ?? 'N/A',
                'email' => $user->email ?? 'N/A',
                'wallet_balance' => number_format((float) ($user->wallet_balance ?? 0), 2),
                'referral_code' => $user->referral_code ?? 'N/A',
                'referred_by' => $user->referred_by ?? 'N/A',
                'profile_image' => $user->profile_image ? asset($user->profile_image) : null,
                'account_status' => strtolower($user->account_status ?? 'inactive'),
                'account_status_text' => strtoupper($user->account_status ?? 'inactive'),
                'email_verified_at' => $this->formatDate($user->email_verified_at),
                'last_login_at' => $this->formatDate($user->last_login_at, 'Never'),
                'last_login_ip' => $user->last_login_ip ?? 'N/A',
                'created_at' => $this->formatDate($user->created_at),
                'updated_at' => $this->formatDate($user->updated_at),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'min:6'],
            'account_status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $firstName = strtoupper(trim($validated['first_name']));
        $lastName = strtoupper(trim($validated['last_name']));
        $fullName = trim($firstName . ' ' . $lastName);
        $phone = $this->cleanPhone($validated['phone']);
        $email = strtolower(trim($validated['email']));
        $plainPassword = trim($validated['password']);

        $duplicate = DB::table('users')
            ->where('phone', $phone)
            ->orWhere('email', $email)
            ->orWhere(function ($query) use ($firstName, $lastName, $phone) {
                $query->whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
                    ->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)])
                    ->where('phone', $phone);
            })
            ->first();

        if ($duplicate) {
            return back()
                ->withInput()
                ->with('error', 'Duplicate user found. Phone, email or same user details already exist.');
        }

        DB::table('users')->insert([
            'user_id' => (string) Str::uuid(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'wallet_balance' => 0,
            'referral_code' => $this->generateReferralCode(),
            'account_status' => $validated['account_status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $smsSent = $this->sendUserLoginSms($fullName, $phone, $email, $plainPassword);

        return back()->with(
            $smsSent ? 'success' : 'warning',
            $smsSent
                ? 'User created successfully. Login SMS sent to user.'
                : 'User created successfully, but SMS could not be sent.'
        );
    }

    public function update(Request $request, string $user_id)
    {
        $user = DB::table('users')->where('user_id', $user_id)->first();

        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($user_id, 'user_id'),
            ],
            'email' => [
                'required',
                'email',
                'max:120',
                Rule::unique('users', 'email')->ignore($user_id, 'user_id'),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'account_status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $firstName = strtoupper(trim($validated['first_name']));
        $lastName = strtoupper(trim($validated['last_name']));

        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => trim($firstName . ' ' . $lastName),
            'phone' => $this->cleanPhone($validated['phone']),
            'email' => strtolower(trim($validated['email'])),
            'account_status' => $validated['account_status'],
            'updated_at' => now(),
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        DB::table('users')->where('user_id', $user_id)->update($data);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(string $user_id)
    {
        $user = DB::table('users')->where('user_id', $user_id)->first();

        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        $ordersCount = DB::table('orders')
            ->where('user_id', $user_id)
            ->count();

        if ($ordersCount > 0) {
            return back()->with(
                'error',
                'This user cannot be deleted because they already have order records. You can suspend or deactivate the account instead.'
            );
        }

        DB::transaction(function () use ($user, $user_id) {
            if (!empty($user->profile_image)) {
                $imagePath = str_replace('storage/', '', $user->profile_image);

                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            }

            DB::table('users')->where('user_id', $user_id)->delete();
        });

        return back()->with('success', 'User deleted successfully.');
    }

    private function sendUserLoginSms(string $name, string $phone, string $email, string $password): bool
    {
        try {
            if (filter_var(env('FAKE_SMS', false), FILTER_VALIDATE_BOOLEAN)) {
                Log::info('FAKE USER LOGIN SMS', compact('name', 'phone', 'email', 'password'));
                return true;
            }

            $message = "Dear {$name}, your KlikDataHub account has been created.\n"
                . "Email: {$email}\n"
                . "Password: {$password}\n"
                . "Login: " . url('/login') . "\n"
                . "Klik DataHub Team";

            $response = Http::timeout(15)->get(env('SMSNOTIFY_API_BASE'), [
                'key' => env('SMSNOTIFY_API_KEY'),
                'to' => $this->formatGhanaPhone($phone),
                'msg' => $message,
                'sender_id' => env('SMS_SENDER_ID', 'KLIKDATAHUB'),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('USER LOGIN SMS FAILED', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function cleanPhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', trim($phone));
    }

    private function formatGhanaPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '233' . substr($phone, 1);
        }

        if (str_starts_with($phone, '233')) {
            return $phone;
        }

        return $phone;
    }

    private function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (DB::table('users')->where('referral_code', $code)->exists());

        return $code;
    }

    private function formatDate($date, string $fallback = 'N/A'): string
    {
        if (empty($date)) {
            return $fallback;
        }

        return \Carbon\Carbon::parse($date)->format('d M Y, h:i A');
    }
}