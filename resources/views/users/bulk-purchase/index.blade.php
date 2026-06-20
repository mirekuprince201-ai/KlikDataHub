@extends('users.layouts.app')

@section('title', 'Bulk Purchase')
@section('page-title', 'Bulk Purchase')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/bulk-purchase.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    use Illuminate\Support\Facades\DB;

    $user = auth('user')->user();
    $walletBalance = (float) ($user->wallet_balance ?? 0);

    $activeCategories = $categories ?? DB::table('categories')
        ->whereRaw('LOWER(status) = ?', ['active'])
        ->orderBy('sort_order')
        ->orderBy('category_name')
        ->get();

    $categoryIds = $activeCategories->pluck('category_id')->filter()->values();

    $plans = DB::table('plans')
        ->whereIn('category_id', $categoryIds)
        ->whereRaw('LOWER(status) = ?', ['active'])
        ->orderBy('selling_price')
        ->get()
        ->map(function ($plan) {
            return [
                'plan_id' => $plan->plan_id,
                'category_id' => $plan->category_id,
                'plan_name' => $plan->plan_name,
                'slug' => $plan->slug,
                'network' => $plan->network,
                'capacity' => $plan->data_volume,
                'price' => (float) $plan->selling_price,
                'status' => $plan->status ?? 'active',
            ];
        })
        ->groupBy('category_id');

    $categoriesData = $activeCategories->map(function ($category) use ($plans) {
        $image = $category->category_image ?? null;

        if (!empty($image)) {
            if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                $imageUrl = $image;
            } elseif (str_starts_with($image, 'storage/')) {
                $imageUrl = asset($image);
            } elseif (str_starts_with($image, 'public/')) {
                $imageUrl = asset('storage/' . substr($image, 7));
            } elseif (str_starts_with($image, 'uploads/')) {
                $imageUrl = asset($image);
            } else {
                $imageUrl = asset(ltrim($image, '/'));
            }
        } else {
            $imageUrl = asset('images/category-placeholder.png');
        }

        return [
            'category_id' => $category->category_id,
            'category_name' => $category->category_name,
            'category_slug' => $category->category_slug,
            'image_url' => $imageUrl,
            'plans' => $plans->get($category->category_id, collect())->values(),
        ];
    })->values();
@endphp

<div class="kd-bulk-page">

    <section class="kd-bulk-hero">
        <div>
            <span class="kd-bulk-badge">
                <i class="fa-solid fa-cart-shopping"></i>
                Bulk Purchase
            </span>

            <h1>
                Buy Data For
                <span>Multiple Numbers</span>
            </h1>

            <p>
                Select a network category, choose from available plans, paste or upload numbers,
                preview validation instantly, and pay securely using your wallet balance.
            </p>
        </div>

        <div class="kd-bulk-wallet">
            <span>Wallet Balance</span>
            <strong>GH₵{{ number_format($walletBalance, 2) }}</strong>
        </div>
    </section>

    @if(session('success'))
        <div class="kd-bulk-alert success">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="kd-bulk-alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="kd-bulk-alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('user.bulk.store') }}" class="kd-bulk-form" id="bulkPurchaseForm">
        @csrf

        <input type="hidden" name="category_id" id="selectedCategoryId">
        <input type="hidden" name="category_slug" id="selectedCategorySlug">
        <input type="hidden" name="orders_payload" id="ordersPayload">
        <input type="hidden" name="total_amount" id="totalAmountInput">

        <div class="kd-bulk-card">
            <div class="kd-bulk-card-head">
                <div>
                    <span>Step 1</span>
                    <h3>Select Network Category</h3>
                </div>
                <i class="fa-solid fa-signal"></i>
            </div>

            @if($categoriesData->count() > 0)
                <div class="kd-bulk-category-grid">
                    @foreach($categoriesData as $category)
                        <label class="kd-bulk-category">
                            <input type="radio"
                                   name="bulk_category"
                                   value="{{ $category['category_id'] }}"
                                   data-slug="{{ $category['category_slug'] }}"
                                   data-name="{{ $category['category_name'] }}">

                            <span class="kd-bulk-category-box">
                                <span class="kd-bulk-category-img">
                                    <img src="{{ $category['image_url'] }}"
                                         alt="{{ $category['category_name'] }}"
                                         loading="lazy"
                                         onerror="this.onerror=null;this.src='{{ asset('images/category-placeholder.png') }}';">
                                </span>

                                <strong>{{ strtoupper($category['category_name']) }}</strong>

                                <span class="kd-bulk-check">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="kd-bulk-empty">
                    <i class="fa-solid fa-layer-group"></i>
                    <h3>No Active Categories</h3>
                    <p>Please add active categories from the admin side.</p>
                </div>
            @endif
        </div>

        <div class="kd-bulk-card" id="plansCard" style="display:none;">
            <div class="kd-bulk-card-head">
                <div>
                    <span>Step 2</span>
                    <h3 id="plansTitle">Available Plans</h3>
                </div>
                <i class="fa-solid fa-tags"></i>
            </div>

            <div class="kd-bulk-plans-grid" id="plansGrid"></div>
        </div>

        <div class="kd-bulk-card">
            <div class="kd-bulk-card-head">
                <div>
                    <span>Step 3</span>
                    <h3>Add Bulk Orders</h3>
                </div>
                <i class="fa-solid fa-address-book"></i>
            </div>

            <div class="kd-bulk-tabs">
                <button type="button" class="kd-bulk-tab active" data-tab="quick">
                    <i class="fa-regular fa-clipboard"></i>
                    Quick Paste
                </button>

                <button type="button" class="kd-bulk-tab" data-tab="manual">
                    <i class="fa-regular fa-pen-to-square"></i>
                    Manual
                </button>

                <button type="button" class="kd-bulk-tab" data-tab="excel">
                    <i class="fa-regular fa-file-excel"></i>
                    Excel
                </button>
            </div>

            <div class="kd-bulk-tab-panel active" id="tabQuick">
                <div class="kd-bulk-info">
                    <strong>Format:</strong> phoneNumber capacity
                    <br>
                    Example: 0551082710 2 or 0241234567 5
                </div>

                <textarea id="quickPasteInput"
                          class="kd-bulk-textarea"
                          rows="10"
                          placeholder="0551082710 2&#10;0241234567 5&#10;0201234567 10"></textarea>

                <button type="button" class="kd-bulk-clear" id="clearQuickPaste">
                    <i class="fa-regular fa-circle-xmark"></i>
                    Clear
                </button>
            </div>

            <div class="kd-bulk-tab-panel" id="tabManual">
                <div class="kd-bulk-info">
                    Choose one default capacity for all rows, or select a different plan for each number.
                </div>

                <div class="kd-manual-default">
                    <label for="defaultManualPlan">Default Capacity for All</label>
                    <select id="defaultManualPlan">
                        <option value="">Select default plan...</option>
                    </select>
                </div>

                <div id="manualRows" class="kd-manual-rows"></div>

                <button type="button" class="kd-add-row" id="addManualRow">
                    <i class="fa-solid fa-plus"></i>
                    Add Order
                    <span id="manualCount">(1/2,000)</span>
                </button>
            </div>

            <div class="kd-bulk-tab-panel" id="tabExcel">
                <div class="kd-excel-actions">
                    <button type="button" class="kd-template-btn" id="downloadTemplate">
                        <i class="fa-regular fa-file-arrow-down"></i>
                        Download Template
                    </button>

                    <span>Columns: phoneNumber | capacity</span>
                </div>

                <label class="kd-upload-box" for="excelFile">
                    <input type="file" id="excelFile" accept=".xlsx,.xls,.csv">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <strong>Click to upload Excel file</strong>
                    <span>.xlsx, .xls, .csv Max 10MB</span>
                </label>

                <p class="kd-excel-note">
                    Prices and validation update automatically using the selected network and available package list.
                </p>
            </div>
        </div>

        <div class="kd-bulk-preview" id="previewBox">
            <div class="kd-preview-head">
                <h3>Bulk Purchase Preview</h3>
                <strong id="validCount">0 valid orders</strong>
            </div>

            <div class="kd-preview-table-wrap">
                <table class="kd-preview-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Phone</th>
                            <th>GB</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="previewBody">
                        <tr>
                            <td colspan="6" class="kd-preview-empty">
                                Select a category and enter orders to preview.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="kd-summary-box">
                <p>
                    <span>Wallet Balance</span>
                    <strong>GH₵{{ number_format($walletBalance, 2) }}</strong>
                </p>

                <p>
                    <span>Total Deduction</span>
                    <strong id="totalDeduction">GH₵0.00</strong>
                </p>

                <p>
                    <span>Balance After Purchase</span>
                    <strong id="balanceAfter">GH₵{{ number_format($walletBalance, 2) }}</strong>
                </p>
            </div>
        </div>

        <div class="kd-bulk-submit-wrap">
            <button type="submit" class="kd-bulk-submit" id="submitBulkBtn" disabled>
                <i class="fa-solid fa-wallet"></i>
                Pay With Wallet
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
    window.KD_BULK_CATEGORIES = @json($categoriesData);
    window.KD_WALLET_BALANCE = Number(@json($walletBalance));
</script>

<script src="{{ asset('users/js/bulk-purchase.js') }}?v={{ time() }}"></script>
@endpush