<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();

        /*
        |--------------------------------------------------------------------------
        | Data Categories
        |--------------------------------------------------------------------------
        */

        $categories = DB::table('categories')
            ->select(
                'category_id',
                'category_name',
                'category_slug',
                'category_image',
                'status',
                'sort_order',
                'created_at'
            )
            ->orderByRaw("CASE WHEN LOWER(status) = 'active' THEN 0 ELSE 1 END")
            ->orderBy('sort_order', 'asc')
            ->orderBy('category_name', 'asc')
            ->get()
            ->map(function ($category) {
                $category->image_url = $this->makeImageUrl(
                    $category->category_image,
                    'images/category-placeholder.png'
                );

                $category->is_active = strtolower((string) ($category->status ?? 'inactive')) === 'active';

                return $category;
            });

        /*
        |--------------------------------------------------------------------------
        | AFA Registrations
        |--------------------------------------------------------------------------
        */

        $afas = DB::table('afa_registrations')
            ->select(
                'afa_registration_id',
                'registration_name',
                'registration_slug',
                'registration_amount',
                'registration_image',
                'status',
                'created_at'
            )
            ->orderByRaw("CASE WHEN LOWER(status) = 'active' THEN 0 ELSE 1 END")
            ->orderBy('registration_name', 'asc')
            ->get()
            ->map(function ($afa) {
                $afa->image_url = $this->makeImageUrl(
                    $afa->registration_image,
                    'images/afa-placeholder.png'
                );

                $afa->is_active = strtolower((string) ($afa->status ?? 'inactive')) === 'active';
                $afa->amount = (float) ($afa->registration_amount ?? 0);

                return $afa;
            });

        /*
        |--------------------------------------------------------------------------
        | Result Checker Categories
        |--------------------------------------------------------------------------
        */

        $resultCheckers = DB::table('result_checker_categories')
            ->select(
                'category_id',
                'category_name',
                'category_slug',
                'category_description',
                'category_image',
                'sort_order',
                'status',
                'created_at'
            )
            ->orderByRaw("CASE WHEN LOWER(status) = 'active' THEN 0 ELSE 1 END")
            ->orderBy('sort_order', 'asc')
            ->orderBy('category_name', 'asc')
            ->get()
            ->map(function ($checker) {
                $checker->image_url = $this->makeImageUrl(
                    $checker->category_image,
                    'images/result-checker-placeholder.png'
                );

                $checker->is_active = strtolower((string) ($checker->status ?? 'inactive')) === 'active';

                return $checker;
            });

        /*
        |--------------------------------------------------------------------------
        | Dashboard Stats
        |--------------------------------------------------------------------------
        */

        $stats = [
            'is_logged_in' => Auth::guard('user')->check(),
            'wallet_balance' => $user ? (float) ($user->wallet_balance ?? 0) : 0,

            'total_categories' => $categories->count(),
            'active_categories' => $categories->where('is_active', true)->count(),
            'inactive_categories' => $categories->where('is_active', false)->count(),

            'total_afas' => $afas->count(),
            'active_afas' => $afas->where('is_active', true)->count(),
            'inactive_afas' => $afas->where('is_active', false)->count(),

            'total_result_checkers' => $resultCheckers->count(),
            'active_result_checkers' => $resultCheckers->where('is_active', true)->count(),
            'inactive_result_checkers' => $resultCheckers->where('is_active', false)->count(),
        ];

        return view('users.dashboard.index', compact(
            'user',
            'categories',
            'afas',
            'resultCheckers',
            'stats'
        ));
    }

    private function makeImageUrl(?string $path, string $fallback): string
    {
        if (empty($path)) {
            return asset($fallback);
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'public/')) {
            $path = 'storage/' . substr($path, 7);
        }

        return asset($path);
    }
}