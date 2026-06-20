<?php

namespace App\Http\Controllers\Admin\ResultChecker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ResultCheckerItemController extends Controller
{
    private array $checkerTypes = [
        'BECE',
        'BECE NOVDEC',
        'WASSCE',
        'WASSCE NOVDEC',
    ];

    public function index()
    {
        $categories = DB::table('result_checker_categories')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        $checkers = DB::table('result_checkers as rc')
            ->leftJoin('result_checker_categories as cat', 'cat.category_id', '=', 'rc.category_id')
            ->select(
                'rc.*',
                'cat.category_name',
                DB::raw("(SELECT COUNT(*) FROM result_checker_vouchers WHERE checker_id = rc.checker_id) as total_stock"),
                DB::raw("(SELECT COUNT(*) FROM result_checker_vouchers WHERE checker_id = rc.checker_id AND sold_status = 'sold') as sold_stock"),
                DB::raw("(SELECT COUNT(*) FROM result_checker_vouchers WHERE checker_id = rc.checker_id AND sold_status = 'available') as available_stock")
            )
            ->orderByDesc('rc.created_at')
            ->get();

        $discounts = DB::table('result_checker_bulk_discounts')
            ->orderBy('min_quantity')
            ->get()
            ->groupBy('checker_id');

        $checkerTypes = $this->checkerTypes;

        return view('admin.result-checkers.index', compact(
            'checkers',
            'categories',
            'checkerTypes',
            'discounts'
        ));
    }

    public function create()
    {
        return redirect()->route('admin.result-checkers.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateCheckerRequest($request);

        $checkerType = strtoupper(trim($data['checker_type']));

        if (!in_array($checkerType, $this->checkerTypes, true)) {
            return back()->withInput()->withErrors([
                'checker_type' => 'Invalid checker type selected.',
            ]);
        }

        $checkerName = $this->makeCheckerName($checkerType);
        $slug = Str::slug($checkerName);

        if (DB::table('result_checkers')->where('checker_slug', $slug)->exists()) {
            return back()->withInput()->withErrors([
                'checker_type' => $checkerName . ' already exists.',
            ]);
        }

        $checkerId = (string) Str::uuid();
        $checkerCode = $this->generateCheckerCode($checkerType);

        $voucherRows = $this->extractVoucherRowsFromRequest($request);
        $this->validateVoucherDuplicates($voucherRows);

        DB::transaction(function () use ($request, $data, $checkerId, $checkerType, $checkerName, $checkerCode, $slug, $voucherRows) {
            DB::table('result_checkers')->insert([
                'checker_id' => $checkerId,
                'category_id' => $data['category_id'],
                'checker_name' => $checkerName,
                'checker_slug' => $slug,
                'checker_type' => $checkerType,
                'checker_code' => $checkerCode,
                'buying_price' => $data['buying_price'] ?? 0,
                'selling_price' => $data['selling_price'],
                'description' => !empty($data['description']) ? strtoupper(trim($data['description'])) : null,
                'status' => $data['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->saveBulkDiscountsFromRequest($request, $checkerId);
            $this->insertVoucherRows($checkerId, $voucherRows);
        });

        return back()->with('success', 'Result checker created successfully.');
    }

    public function update(Request $request, string $checker_id)
    {
        $checker = DB::table('result_checkers')
            ->where('checker_id', $checker_id)
            ->first();

        if (!$checker) {
            return back()->withErrors(['checker' => 'Result checker not found.']);
        }

        $data = $this->validateCheckerRequest($request);

        $checkerType = strtoupper(trim($data['checker_type']));

        if (!in_array($checkerType, $this->checkerTypes, true)) {
            return back()->withInput()->withErrors([
                'checker_type' => 'Invalid checker type selected.',
            ]);
        }

        $checkerName = $this->makeCheckerName($checkerType);
        $slug = Str::slug($checkerName);

        $slugExists = DB::table('result_checkers')
            ->where('checker_slug', $slug)
            ->where('checker_id', '!=', $checker_id)
            ->exists();

        if ($slugExists) {
            return back()->withInput()->withErrors([
                'checker_type' => $checkerName . ' already exists.',
            ]);
        }

        $voucherRows = $this->extractVoucherRowsFromRequest($request);
        $this->validateVoucherDuplicates($voucherRows);

        DB::transaction(function () use ($request, $data, $checker_id, $checkerType, $checkerName, $slug, $checker, $voucherRows) {
            DB::table('result_checkers')
                ->where('checker_id', $checker_id)
                ->update([
                    'category_id' => $data['category_id'],
                    'checker_name' => $checkerName,
                    'checker_slug' => $slug,
                    'checker_type' => $checkerType,
                    'checker_code' => $checker->checker_code,
                    'buying_price' => $data['buying_price'] ?? 0,
                    'selling_price' => $data['selling_price'],
                    'description' => !empty($data['description']) ? strtoupper(trim($data['description'])) : null,
                    'status' => $data['status'],
                    'updated_at' => now(),
                ]);

            $this->saveBulkDiscountsFromRequest($request, $checker_id);
            $this->insertVoucherRows($checker_id, $voucherRows);
        });

        return back()->with('success', 'Result checker updated successfully.');
    }

    public function destroy(string $checker_id)
    {
        $sold = DB::table('result_checker_vouchers')
            ->where('checker_id', $checker_id)
            ->where('sold_status', 'sold')
            ->exists();

        if ($sold) {
            return back()->withErrors([
                'delete' => 'You cannot delete this checker because some vouchers have already been sold.',
            ]);
        }

        DB::transaction(function () use ($checker_id) {
            DB::table('result_checker_bulk_discounts')->where('checker_id', $checker_id)->delete();
            DB::table('result_checker_vouchers')->where('checker_id', $checker_id)->delete();
            DB::table('result_checkers')->where('checker_id', $checker_id)->delete();
        });

        return back()->with('success', 'Result checker deleted successfully.');
    }

    public function importVouchers(Request $request, string $checker_id)
    {
        $request->validate([
            'voucher_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $checker = DB::table('result_checkers')
            ->where('checker_id', $checker_id)
            ->first();

        if (!$checker) {
            return back()->withErrors(['checker' => 'Result checker not found.']);
        }

        $voucherRows = $this->extractVoucherRowsFromFile($request->file('voucher_file'));
        $this->validateVoucherDuplicates($voucherRows);

        DB::transaction(function () use ($checker_id, $voucherRows) {
            $this->insertVoucherRows($checker_id, $voucherRows);
        });

        return back()->with('success', count($voucherRows) . ' vouchers imported successfully.');
    }

    public function downloadVoucherTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="result-checker-voucher-template.csv"',
        ];

        return response()->stream(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['serial_number', 'voucher_pin']);
            fputcsv($file, ['1234567890', '9876543210']);
            fputcsv($file, ['1234567891', '9876543211']);
            fclose($file);
        }, 200, $headers);
    }

    private function validateCheckerRequest(Request $request): array
    {
        return $request->validate([
            'category_id' => 'required|string|exists:result_checker_categories,category_id',
            'checker_type' => 'required|string|max:100',
            'buying_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',

            'voucher_entry_method' => 'nullable|in:manual,excel',
            'serial_numbers' => 'nullable|array',
            'serial_numbers.*' => 'nullable|string|max:255',
            'voucher_pins' => 'nullable|array',
            'voucher_pins.*' => 'nullable|string|max:255',
            'voucher_file' => 'nullable|file|mimes:xlsx,xls,csv|max:5120',

            'discount_min_quantities' => 'nullable|array',
            'discount_min_quantities.*' => 'nullable|integer|min:1',
            'discount_max_quantities' => 'nullable|array',
            'discount_max_quantities.*' => 'nullable|integer|min:1',
            'discount_unit_prices' => 'nullable|array',
            'discount_unit_prices.*' => 'nullable|numeric|min:0',
        ]);
    }

    private function saveBulkDiscountsFromRequest(Request $request, string $checkerId): void
    {
        DB::table('result_checker_bulk_discounts')
            ->where('checker_id', $checkerId)
            ->delete();

        $mins = $request->input('discount_min_quantities', []);
        $maxs = $request->input('discount_max_quantities', []);
        $prices = $request->input('discount_unit_prices', []);

        foreach ($mins as $index => $minQuantity) {
            $min = (int) $minQuantity;
            $maxValue = $maxs[$index] ?? null;
            $unitPrice = $prices[$index] ?? null;

            if ($min <= 0 || $unitPrice === null || $unitPrice === '') {
                continue;
            }

            $max = $maxValue !== null && $maxValue !== '' ? (int) $maxValue : null;

            if ($max !== null && $max < $min) {
                continue;
            }

            DB::table('result_checker_bulk_discounts')->insert([
                'discount_id' => (string) Str::uuid(),
                'checker_id' => $checkerId,
                'min_quantity' => $min,
                'max_quantity' => $max,
                'unit_price' => (float) $unitPrice,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function getUnitPriceForQuantity(string $checkerId, int $quantity): float
    {
        $checker = DB::table('result_checkers')
            ->where('checker_id', $checkerId)
            ->first();

        if (!$checker) {
            return 0;
        }

        $discount = DB::table('result_checker_bulk_discounts')
            ->where('checker_id', $checkerId)
            ->where('status', 'active')
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderByDesc('min_quantity')
            ->first();

        return $discount ? (float) $discount->unit_price : (float) $checker->selling_price;
    }

    private function makeCheckerName(string $checkerType): string
    {
        return match ($checkerType) {
            'BECE' => 'BECE RESULT CHECKER',
            'BECE NOVDEC' => 'BECE NOVDEC RESULT CHECKER',
            'WASSCE' => 'WASSCE RESULT CHECKER',
            'WASSCE NOVDEC' => 'WASSCE NOVDEC RESULT CHECKER',
            default => strtoupper($checkerType) . ' RESULT CHECKER',
        };
    }

    private function generateCheckerCode(string $checkerType): string
    {
        $prefix = match ($checkerType) {
            'BECE' => 'BECE',
            'BECE NOVDEC' => 'BECE-NOV',
            'WASSCE' => 'WASSCE',
            'WASSCE NOVDEC' => 'WASSCE-NOV',
            default => 'CHK',
        };

        do {
            $code = $prefix . '-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));

            $exists = DB::table('result_checkers')
                ->where('checker_code', $code)
                ->exists();
        } while ($exists);

        return $code;
    }

    private function extractVoucherRowsFromRequest(Request $request): array
    {
        $method = $request->input('voucher_entry_method');

        if ($method === 'excel' && $request->hasFile('voucher_file')) {
            return $this->extractVoucherRowsFromFile($request->file('voucher_file'));
        }

        if ($method === 'manual') {
            return $this->extractVoucherRowsFromManual(
                $request->input('serial_numbers', []),
                $request->input('voucher_pins', [])
            );
        }

        return [];
    }

    private function extractVoucherRowsFromManual(array $serialNumbers, array $pins): array
    {
        $rows = [];

        foreach ($serialNumbers as $index => $serialNumber) {
            $serial = strtoupper(trim((string) $serialNumber));
            $pin = trim((string) ($pins[$index] ?? ''));

            if ($serial === '' && $pin === '') {
                continue;
            }

            if ($serial === '' || $pin === '') {
                throw ValidationException::withMessages([
                    'vouchers' => 'Each voucher must have both serial number and PIN.',
                ]);
            }

            $rows[] = [
                'serial_number' => $serial,
                'voucher_pin' => $pin,
            ];
        }

        return $rows;
    }

    private function extractVoucherRowsFromFile($file): array
    {
        $excelRows = Excel::toArray([], $file)[0] ?? [];
        $rows = [];

        foreach ($excelRows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $serial = strtoupper(trim((string) ($row[0] ?? '')));
            $pin = trim((string) ($row[1] ?? ''));

            if ($serial === '' && $pin === '') {
                continue;
            }

            if ($serial === '' || $pin === '') {
                throw ValidationException::withMessages([
                    'voucher_file' => 'Every Excel row must have both serial number and PIN. Check row ' . ($index + 1) . '.',
                ]);
            }

            $rows[] = [
                'serial_number' => $serial,
                'voucher_pin' => $pin,
            ];
        }

        return $rows;
    }

    private function validateVoucherDuplicates(array $voucherRows): void
    {
        if (empty($voucherRows)) {
            return;
        }

        $serials = [];
        $pins = [];

        foreach ($voucherRows as $row) {
            $serial = $row['serial_number'];
            $pin = $row['voucher_pin'];

            if (isset($serials[$serial])) {
                throw ValidationException::withMessages([
                    'vouchers' => 'Duplicate serial number found in your entry: ' . $serial,
                ]);
            }

            if (isset($pins[$pin])) {
                throw ValidationException::withMessages([
                    'vouchers' => 'Duplicate PIN found in your entry: ' . $pin,
                ]);
            }

            $serials[$serial] = true;
            $pins[$pin] = true;
        }

        $existingSerial = DB::table('result_checker_vouchers')
            ->whereIn('serial_number', array_keys($serials))
            ->value('serial_number');

        if ($existingSerial) {
            throw ValidationException::withMessages([
                'vouchers' => 'This serial number already exists: ' . $existingSerial,
            ]);
        }

        $existingPin = DB::table('result_checker_vouchers')
            ->whereIn('voucher_pin', array_keys($pins))
            ->value('voucher_pin');

        if ($existingPin) {
            throw ValidationException::withMessages([
                'vouchers' => 'This PIN already exists: ' . $existingPin,
            ]);
        }
    }

    private function insertVoucherRows(string $checkerId, array $voucherRows): void
    {
        foreach ($voucherRows as $row) {
            DB::table('result_checker_vouchers')->insert([
                'voucher_id' => (string) Str::uuid(),
                'checker_id' => $checkerId,
                'serial_number' => $row['serial_number'],
                'voucher_pin' => $row['voucher_pin'],
                'sold_status' => 'available',
                'sold_to' => null,
                'sold_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}