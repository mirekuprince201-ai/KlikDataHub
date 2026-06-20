@extends('admin.layouts.app')

@section('title', 'Result Checkers')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/result-checkers.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('admin/css/result-checker-forms.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="kd-checker-page">
    <div class="kd-checker-shell">

        <div class="kd-page-head">
            <div>
                <span><i class="fa-solid fa-square-poll-vertical"></i> Result Checkers</span>
                <h1>Result Checker Stock</h1>
                <p>Manage checker cards, PIN stock, serial numbers and bulk discount pricing.</p>
            </div>

            <button type="button" class="kd-btn-primary" data-kd-open-sidebar="addCheckerSidebar">
                <i class="fa-solid fa-plus"></i>
                Add Checker
            </button>
        </div>

        {{-- @if (session('success'))
            <div class="kd-alert success">
                <i class="fa-solid fa-circle-check"></i>
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="kd-alert error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif --}}

        <div class="kd-stats-grid">
            <div class="kd-stat-card">
                <span>Total Checkers</span>
                <h3>{{ number_format($checkers->count()) }}</h3>
            </div>

            <div class="kd-stat-card">
                <span>Available Stock</span>
                <h3>{{ number_format($checkers->sum('available_stock')) }}</h3>
            </div>

            <div class="kd-stat-card">
                <span>Sold Stock</span>
                <h3>{{ number_format($checkers->sum('sold_stock')) }}</h3>
            </div>

            <div class="kd-stat-card">
                <span>Total Stock</span>
                <h3>{{ number_format($checkers->sum('total_stock')) }}</h3>
            </div>
        </div>

        <div class="kd-table-card">
            <div class="kd-table-head">
                <div>
                    <span>Inventory</span>
                    <h3>Available Result Checkers</h3>
                </div>
            </div>

            <div class="kd-dt-toolbar" id="kdResultCheckerToolbar">
                <div class="kd-dt-left" id="kdResultCheckerLength"></div>
                <div class="kd-dt-center" id="kdResultCheckerButtons"></div>
                <div class="kd-dt-right" id="kdResultCheckerSearch"></div>
            </div>

            <div class="kd-table-wrap">
                <table id="kdResultCheckerTable" class="kd-table display nowrap">
                    <thead>
                        <tr>
                            <th>Checker</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Code</th>
                            <th>Buying</th>
                            <th>Selling</th>
                            <th>Bulk Discounts</th>
                            <th>Available</th>
                            <th>Sold</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($checkers as $checker)
                            @php
                                $checkerDiscounts = isset($discounts)
                                    ? ($discounts[$checker->checker_id] ?? collect())
                                    : collect();
                            @endphp

                            <tr>
                                <td>
                                    <div class="kd-main-cell">
                                        <strong>{{ $checker->checker_name }}</strong>
                                        @if(!empty($checker->description))
                                            <small>{{ $checker->description }}</small>
                                        @endif
                                    </div>
                                </td>

                                <td>{{ $checker->checker_type }}</td>
                                <td>{{ $checker->category_name ?? 'N/A' }}</td>
                                <td>{{ $checker->checker_code }}</td>
                                <td>GHS {{ number_format((float) $checker->buying_price, 2) }}</td>
                                <td>GHS {{ number_format((float) $checker->selling_price, 2) }}</td>

                                <td>
                                    @if ($checkerDiscounts->count())
                                        <div class="kd-discount-list">
                                            @foreach ($checkerDiscounts as $discount)
                                                <span>
                                                    {{ $discount->min_quantity }}{{ $discount->max_quantity ? ' - ' . $discount->max_quantity : '+' }}
                                                    = GHS {{ number_format((float) $discount->unit_price, 2) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="kd-muted-pill">No discount</span>
                                    @endif
                                </td>

                                <td><span class="kd-stock available">{{ number_format($checker->available_stock) }}</span></td>
                                <td><span class="kd-stock sold">{{ number_format($checker->sold_stock) }}</span></td>
                                <td><span class="kd-stock total">{{ number_format($checker->total_stock) }}</span></td>

                                <td>
                                    <span class="kd-status {{ strtolower($checker->status) }}">
                                        {{ ucfirst($checker->status) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="kd-action-row">
                                        <button type="button"
                                                class="kd-icon-btn view"
                                                title="View Details"
                                                data-kd-open-sidebar="viewCheckerSidebar{{ $checker->checker_id }}">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        <button type="button"
                                                class="kd-icon-btn edit"
                                                title="Edit Checker"
                                                data-kd-open-sidebar="editCheckerSidebar{{ $checker->checker_id }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <button type="button"
                                                class="kd-icon-btn upload"
                                                title="Import Stock"
                                                data-kd-open-sidebar="importVoucherSidebar{{ $checker->checker_id }}">
                                            <i class="fa-solid fa-file-excel"></i>
                                        </button>

                                        <button type="button"
                                                class="kd-icon-btn delete"
                                                title="Delete Checker"
                                                data-kd-delete
                                                data-title="{{ $checker->checker_name }}"
                                                data-url="{{ route('admin.result-checkers.destroy', $checker->checker_id) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="kd-dt-footerbar">
                <div id="kdResultCheckerInfo"></div>
                <div id="kdResultCheckerPagination"></div>
            </div>
        </div>

    </div>
</div>

<div class="kd-result-sidebar" id="addCheckerSidebar">
    <div class="kd-result-sidebar-panel">
        <div class="kd-result-sidebar-head">
            <div>
                <span>Add Checker</span>
                <h3>Create Result Checker</h3>
            </div>

            <button type="button" class="kd-result-sidebar-close" data-kd-close-sidebar>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.result-checkers.store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="kd-sidebar-form">
            @csrf

            @include('admin.result-checkers.partials.form', [
                'checker' => null,
                'checkerDiscounts' => collect(),
                'categories' => $categories,
                'checkerTypes' => $checkerTypes,
                'buttonText' => 'Save Checker'
            ])
        </form>
    </div>
</div>

@foreach ($checkers as $checker)
    @php
        $checkerDiscounts = isset($discounts)
            ? ($discounts[$checker->checker_id] ?? collect())
            : collect();
    @endphp

    <div class="kd-result-sidebar" id="viewCheckerSidebar{{ $checker->checker_id }}">
        <div class="kd-result-sidebar-panel">
            <div class="kd-result-sidebar-head">
                <div>
                    <span>Checker Details</span>
                    <h3>{{ $checker->checker_name }}</h3>
                </div>

                <button type="button" class="kd-result-sidebar-close" data-kd-close-sidebar>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="kd-view-body">
                <div class="kd-view-card">
                    <h4>Basic Information</h4>

                    <div class="kd-view-grid">
                        <div><span>Checker Name</span><strong>{{ $checker->checker_name }}</strong></div>
                        <div><span>Type</span><strong>{{ $checker->checker_type }}</strong></div>
                        <div><span>Category</span><strong>{{ $checker->category_name ?? 'N/A' }}</strong></div>
                        <div><span>Code</span><strong>{{ $checker->checker_code }}</strong></div>
                        <div><span>Buying Price</span><strong>GHS {{ number_format((float) $checker->buying_price, 2) }}</strong></div>
                        <div><span>Selling Price</span><strong>GHS {{ number_format((float) $checker->selling_price, 2) }}</strong></div>
                        <div><span>Available Stock</span><strong>{{ number_format($checker->available_stock) }}</strong></div>
                        <div><span>Sold Stock</span><strong>{{ number_format($checker->sold_stock) }}</strong></div>
                        <div><span>Total Stock</span><strong>{{ number_format($checker->total_stock) }}</strong></div>
                        <div><span>Status</span><strong>{{ ucfirst($checker->status) }}</strong></div>
                    </div>

                    @if(!empty($checker->description))
                        <div class="kd-view-description">
                            <span>Description</span>
                            <p>{{ $checker->description }}</p>
                        </div>
                    @endif
                </div>

                <div class="kd-view-card">
                    <h4>Bulk Discount Pricing</h4>

                    @if ($checkerDiscounts->count())
                        <div class="kd-view-discounts">
                            @foreach ($checkerDiscounts as $discount)
                                <div>
                                    <span>
                                        {{ $discount->min_quantity }}{{ $discount->max_quantity ? ' - ' . $discount->max_quantity : '+' }} Cards
                                    </span>
                                    <strong>GHS {{ number_format((float) $discount->unit_price, 2) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="kd-empty-discount">
                            <i class="fa-solid fa-tags"></i>
                            No bulk discount configured.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="kd-result-sidebar" id="editCheckerSidebar{{ $checker->checker_id }}">
        <div class="kd-result-sidebar-panel">
            <div class="kd-result-sidebar-head">
                <div>
                    <span>Edit Checker</span>
                    <h3>{{ $checker->checker_name }}</h3>
                </div>

                <button type="button" class="kd-result-sidebar-close" data-kd-close-sidebar>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('admin.result-checkers.update', $checker->checker_id) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="kd-sidebar-form">
                @csrf
                @method('PUT')

                @include('admin.result-checkers.partials.form', [
                    'checker' => $checker,
                    'checkerDiscounts' => $checkerDiscounts,
                    'categories' => $categories,
                    'checkerTypes' => $checkerTypes,
                    'buttonText' => 'Update Checker'
                ])
            </form>
        </div>
    </div>

    <div class="kd-result-sidebar" id="importVoucherSidebar{{ $checker->checker_id }}">
        <div class="kd-result-sidebar-panel">
            <div class="kd-result-sidebar-head">
                <div>
                    <span>Import Stock</span>
                    <h3>{{ $checker->checker_name }}</h3>
                </div>

                <button type="button" class="kd-result-sidebar-close" data-kd-close-sidebar>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="kd-import-note">
                <i class="fa-solid fa-circle-info"></i>
                Upload Excel with <strong>Serial Number</strong> in column A and <strong>PIN / Password</strong> in column B.
            </div>

            <form action="{{ route('admin.result-checkers.vouchers.import', $checker->checker_id) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="kd-sidebar-form">
                @csrf

                <div class="kd-form-group">
                    <div class="kd-upload-top">
                        <label>Excel File</label>

                        <a href="{{ route('admin.result-checkers.vouchers.template') }}" class="kd-template-btn">
                            <i class="fa-solid fa-download"></i>
                            Download Template
                        </a>
                    </div>

                    <div class="kd-upload-box">
                        <input type="file"
                               name="voucher_file"
                               id="import_voucher_file_{{ $checker->checker_id }}"
                               accept=".xlsx,.xls,.csv"
                               required>

                        <label for="import_voucher_file_{{ $checker->checker_id }}" class="kd-upload-label">
                            <i class="fa-solid fa-file-excel"></i>
                            <strong>Upload Excel File</strong>
                            <span>Column A: Serial Number, Column B: PIN / Password</span>
                        </label>
                    </div>
                </div>

                <div class="kd-sidebar-actions">
                    <button type="button" class="kd-btn-light" data-kd-close-sidebar>
                        <i class="fa-solid fa-xmark"></i>
                        Cancel
                    </button>

                    <button type="submit" class="kd-btn-primary">
                        <i class="fa-solid fa-upload"></i>
                        Import Vouchers
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<div class="kd-delete-modal" id="kdDeleteModal">
    <div class="kd-delete-box">
        <div class="kd-delete-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3>Delete Result Checker?</h3>
        <p id="kdDeleteText">This action cannot be undone.</p>

        <form method="POST" id="kdDeleteForm">
            @csrf
            @method('DELETE')

            <div class="kd-modal-actions">
                <button type="button" class="kd-btn-light" id="kdCancelDelete">
                    Cancel
                </button>

                <button type="submit" class="kd-btn-danger">
                    Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/js/result-checkers.js') }}?v={{ time() }}"></script>
@endpush