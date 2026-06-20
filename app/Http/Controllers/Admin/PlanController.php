<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        $plans = DB::table('plans')
            ->leftJoin('categories', 'plans.category_id', '=', 'categories.category_id')
            ->select(
                'plans.*',
                'categories.category_name',
                'categories.category_slug',
                'categories.category_image'
            )
            ->orderBy('categories.sort_order')
            ->orderBy('categories.category_name')
            ->orderByRaw('CAST(REPLACE(UPPER(plans.data_volume), "GB", "") AS DECIMAL(10,2)) ASC')
            ->orderBy('plans.selling_price')
            ->get();

        $categories = DB::table('categories')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        return view('admin.plans.index', compact('plans', 'categories'));
    }

    public function store(Request $request)
    {
        Log::info('PLAN STORE REQUEST', $request->except(['_token']));

        $validated = $request->validate([
            'category_id' => ['required', 'string', Rule::exists('categories', 'category_id')],
            'network' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'plans' => ['required', 'array', 'min:1'],
            'plans.*.data_volume' => ['required', 'string', 'max:80'],
            'plans.*.selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $category = DB::table('categories')
            ->where('category_id', $validated['category_id'])
            ->first();

        if (!$category) {
            return back()->withInput()->with('error', 'Selected category was not found.');
        }

        $network = $this->resolveNetwork(
            $validated['network'] ?? null,
            $category->category_name ?? '',
            $category->category_slug ?? ''
        );

        if (!$network) {
            return back()->withInput()->with('error', 'Unable to detect network from selected category.');
        }

        $rows = [];
        $seenVolumes = [];
        $duplicateMessages = [];
        $adminId = $this->adminId();
        $status = $validated['status'];
        $now = now();

        foreach ($validated['plans'] as $planItem) {
            $dataVolume = $this->normalizeDataVolume($planItem['data_volume'] ?? '');
            $sellingPrice = (float) ($planItem['selling_price'] ?? 0);

            if (!$dataVolume) {
                continue;
            }

            $volumeKey = strtolower($dataVolume);

            if (in_array($volumeKey, $seenVolumes, true)) {
                $duplicateMessages[] = $dataVolume . ' was entered more than once.';
                continue;
            }

            $seenVolumes[] = $volumeKey;

            $planName = $this->buildPlanName($category->category_name, $dataVolume);

            $exists = DB::table('plans')
                ->where('category_id', $category->category_id)
                ->whereRaw('LOWER(data_volume) = ?', [strtolower($dataVolume)])
                ->exists();

            if ($exists) {
                $duplicateMessages[] = $planName . ' already exists.';
                continue;
            }

            $rows[] = [
                'plan_id' => (string) Str::uuid(),
                'category_id' => $category->category_id,
                'plan_name' => strtoupper($planName),
                'slug' => $this->generateUniqueSlug($planName),
                'network' => $network,
                'data_volume' => strtoupper($dataVolume),
                'selling_price' => $sellingPrice,
                'description' => null,
                'status' => $status,
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (count($rows) < 1) {
            $message = count($duplicateMessages) > 0
                ? implode(' ', array_unique($duplicateMessages))
                : 'No valid plan was submitted.';

            return back()->withInput()->with('error', $message);
        }

        try {
            DB::table('plans')->insert($rows);

            $message = count($rows) === 1
                ? 'Plan created successfully.'
                : count($rows) . ' plans created successfully.';

            if (count($duplicateMessages) > 0) {
                $message .= ' Some duplicates were skipped.';
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('PLAN STORE ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Unable to create plans. Please try again.');
        }
    }

    public function show(string $plan_id)
    {
        $plan = DB::table('plans')
            ->leftJoin('categories', 'plans.category_id', '=', 'categories.category_id')
            ->select(
                'plans.*',
                'categories.category_name',
                'categories.category_slug',
                'categories.category_image'
            )
            ->where('plans.plan_id', $plan_id)
            ->first();

        if (!$plan) {
            return response()->json([
                'status' => false,
                'message' => 'Plan not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, string $plan_id)
    {
        Log::info('PLAN UPDATE RAW REQUEST', [
            'plan_id' => $plan_id,
            'request' => $request->except(['_token']),
        ]);

        $plan = DB::table('plans')
            ->where('plan_id', $plan_id)
            ->first();

        if (!$plan) {
            return back()->with('error', 'Plan not found.');
        }

        $categoryId = $request->input('category_id')
            ?: $request->input('edit_category_id')
            ?: $plan->category_id;

        $network = $request->input('network')
            ?: $request->input('edit_network')
            ?: $plan->network;

        $dataVolumeInput = $request->input('data_volume')
            ?: $request->input('edit_data_volume')
            ?: $request->input('plan_data_volume')
            ?: $plan->data_volume;

        $sellingPrice = $request->input('selling_price')
            ?: $request->input('edit_selling_price')
            ?: $plan->selling_price;

        $status = $request->input('status')
            ?: $request->input('edit_status')
            ?: $plan->status;

        $request->merge([
            'category_id' => $categoryId,
            'network' => $network,
            'data_volume' => $dataVolumeInput,
            'selling_price' => $sellingPrice,
            'status' => $status,
        ]);

        Log::info('PLAN UPDATE MERGED REQUEST', [
            'plan_id' => $plan_id,
            'request' => $request->except(['_token']),
        ]);

        $validated = $request->validate([
            'category_id' => ['required', 'string', Rule::exists('categories', 'category_id')],
            'network' => ['nullable', 'string', 'max:80'],
            'data_volume' => ['required', 'string', 'max:80'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $category = DB::table('categories')
            ->where('category_id', $validated['category_id'])
            ->first();

        if (!$category) {
            return back()->withInput()->with('error', 'Selected category was not found.');
        }

        $network = $this->resolveNetwork(
            $validated['network'] ?? null,
            $category->category_name ?? '',
            $category->category_slug ?? ''
        );

        if (!$network) {
            return back()->withInput()->with('error', 'Unable to detect network from selected category.');
        }

        $dataVolume = $this->normalizeDataVolume($validated['data_volume']);

        if (!$dataVolume) {
            return back()->withInput()->with('error', 'Please enter a valid data volume.');
        }

        $planName = $this->buildPlanName($category->category_name, $dataVolume);

        $duplicate = DB::table('plans')
            ->where('category_id', $category->category_id)
            ->whereRaw('LOWER(data_volume) = ?', [strtolower($dataVolume)])
            ->where('plan_id', '!=', $plan_id)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', $planName . ' already exists.');
        }

        $slug = $plan->slug;

        if (
            strtolower($planName) !== strtolower($plan->plan_name) ||
            strtolower($dataVolume) !== strtolower($plan->data_volume)
        ) {
            $slug = $this->generateUniqueSlug($planName, $plan_id);
        }

        try {
            DB::table('plans')
                ->where('plan_id', $plan_id)
                ->update([
                    'category_id' => $category->category_id,
                    'plan_name' => strtoupper($planName),
                    'slug' => $slug,
                    'network' => $network,
                    'data_volume' => strtoupper($dataVolume),
                    'selling_price' => (float) $validated['selling_price'],
                    'description' => null,
                    'status' => $validated['status'],
                    'updated_at' => now(),
                ]);

            Log::info('PLAN UPDATED SUCCESSFULLY', [
                'plan_id' => $plan_id,
                'plan_name' => strtoupper($planName),
                'data_volume' => strtoupper($dataVolume),
                'selling_price' => (float) $validated['selling_price'],
            ]);

            return back()->with('success', 'Plan updated successfully.');
        } catch (\Throwable $e) {
            Log::error('PLAN UPDATE ERROR', [
                'plan_id' => $plan_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Unable to update plan. Please try again.');
        }
    }

    public function updateStatus(Request $request, string $plan_id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $plan = DB::table('plans')
            ->where('plan_id', $plan_id)
            ->first();

        if (!$plan) {
            return back()->with('error', 'Plan not found.');
        }

        DB::table('plans')
            ->where('plan_id', $plan_id)
            ->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Plan status updated successfully.');
    }

    public function destroy(string $plan_id)
    {
        $plan = DB::table('plans')
            ->where('plan_id', $plan_id)
            ->first();

        if (!$plan) {
            return back()->with('error', 'Plan not found.');
        }

        try {
            DB::table('plans')
                ->where('plan_id', $plan_id)
                ->delete();

            return back()->with('success', 'Plan deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('PLAN DELETE ERROR', [
                'plan_id' => $plan_id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to delete plan. Please try again.');
        }
    }

    public function status(string $plan_id)
    {
        $plan = DB::table('plans')
            ->where('plan_id', $plan_id)
            ->first();

        if (!$plan) {
            return back()->with('error', 'Plan not found.');
        }

        $newStatus = strtolower($plan->status) === 'active' ? 'inactive' : 'active';

        DB::table('plans')
            ->where('plan_id', $plan_id)
            ->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Plan status updated successfully.');
    }

    private function buildPlanName(string $categoryName, string $dataVolume): string
    {
        return strtoupper(trim($categoryName)) . ' ' . strtoupper(trim($dataVolume));
    }

    private function normalizeDataVolume(?string $value): string
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '') {
            return '';
        }

        $value = str_replace([' ', 'GB'], '', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);

        if ($value === '') {
            return '';
        }

        if (substr_count($value, '.') > 1) {
            $parts = explode('.', $value);
            $value = array_shift($parts) . '.' . implode('', $parts);
        }

        $value = ltrim($value, '0');

        if ($value === '') {
            $value = '0';
        }

        if (str_starts_with($value, '.')) {
            $value = '0' . $value;
        }

        return $value . 'GB';
    }

    private function resolveNetwork(?string $network, string $categoryName, string $categorySlug): ?string
    {
        $network = strtoupper(trim((string) $network));

        if (in_array($network, ['MTN', 'TELECEL', 'AT'], true)) {
            return $network;
        }

        $source = strtolower($categoryName . ' ' . $categorySlug);

        if (str_contains($source, 'mtn')) {
            return 'MTN';
        }

        if (
            str_contains($source, 'telecel') ||
            str_contains($source, 'voda') ||
            str_contains($source, 'vodafone')
        ) {
            return 'TELECEL';
        }

        if (
            str_contains($source, 'airteltigo') ||
            str_contains($source, 'airtel') ||
            str_contains($source, 'tigo') ||
            str_contains($source, 'ishare') ||
            str_contains($source, 'bigtime')
        ) {
            return 'AT';
        }

        return null;
    }

    private function generateUniqueSlug(string $name, ?string $ignorePlanId = null): string
    {
        $baseSlug = Str::slug($name) ?: Str::random(8);
        $slug = $baseSlug;
        $counter = 1;

        while (
            DB::table('plans')
                ->where('slug', $slug)
                ->when($ignorePlanId, function ($query) use ($ignorePlanId) {
                    $query->where('plan_id', '!=', $ignorePlanId);
                })
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function adminId(): string
    {
        $admin = Auth::guard('admin')->user();

        return $admin->admin_id
            ?? $admin->id
            ?? (string) Str::uuid();
    }
}