@extends('admin.layouts.app')

@section('title', 'Plans')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="{{ asset('admin/css/plans.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('admin/css/plan-sidebar.css') }}?v={{ time() }}">
@endpush

@section('content')

<div class="kd-plan-page">

    <div class="kd-plan-header">
        <div>
            <span class="kd-page-badge">
                <i class="fa-solid fa-wifi"></i>
                Plan Management
            </span>

            <h1>
                <i class="fa-solid fa-signal"></i>
                Data Plans
            </h1>

            <p>Manage data bundle plans, selling prices, categories, and status.</p>
        </div>

        <button type="button" class="kd-add-plan-btn" id="openAddPlanSidebar">
            <i class="fa-solid fa-plus"></i>
            <span>Add Plans</span>
        </button>
    </div>

    <div class="kd-plan-stats">
        <div class="kd-plan-stat-card">
            <div class="kd-stat-icon total">
                <i class="fa-solid fa-list"></i>
            </div>
            <div>
                <span>Total Plans</span>
                <strong>{{ $plans->count() }}</strong>
            </div>
        </div>

        <div class="kd-plan-stat-card">
            <div class="kd-stat-icon active">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span>Active Plans</span>
                <strong>{{ $plans->where('status', 'active')->count() }}</strong>
            </div>
        </div>

        <div class="kd-plan-stat-card">
            <div class="kd-stat-icon inactive">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <span>Inactive Plans</span>
                <strong>{{ $plans->where('status', 'inactive')->count() }}</strong>
            </div>
        </div>
    </div>

    <div class="kd-plan-table-card">

        <div class="kd-table-card-header">
            <div>
                <h2>
                    <i class="fa-solid fa-table-list"></i>
                    Plan Records
                </h2>
                <p>Update, activate, deactivate, and manage all plan records.</p>
            </div>

            <div id="planExportContainer" class="kd-export-container"></div>
        </div>

        <div class="kd-plan-filter-row">
            <div class="kd-filter-group">
                <label for="categoryFilter">Filter Category</label>
                <select id="categoryFilter" class="kd-category-filter">
                    <option value="">All Categories</option>

                    @foreach($plans->pluck('category_name')->filter()->unique()->sort()->values() as $categoryName)
                        <option value="{{ trim($categoryName) }}">
                            {{ trim($categoryName) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="kd-table-responsive">
            <table id="planTable" class="kd-plan-table display nowrap">
                <thead>
                    <tr>
                        <th class="kd-col-no">#</th>
                        <th class="kd-col-plan">Plan</th>
                        <th class="kd-col-category">Category</th>
                        <th class="kd-col-volume">Volume</th>
                        <th class="kd-col-price">Price</th>
                        <th class="kd-col-status">Current Status</th>
                        <th class="kd-col-switch no-export">Status Switch</th>
                        <th class="kd-col-created">Created</th>
                        <th class="kd-col-action no-export">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($plans as $plan)
                        @php
                            $isActive = strtolower($plan->status ?? '') === 'active';
                            $categoryImage = $plan->category_image ?? null;
                            $categoryName = trim($plan->category_name ?? 'N/A');
                        @endphp

                        <tr>
                            <td class="kd-col-no">{{ $loop->iteration }}</td>

                            <td class="kd-col-plan">
                                <div class="kd-plan-info">
                                    @if(!empty($categoryImage))
                                        <img src="{{ asset($categoryImage) }}"
                                             alt="{{ $plan->plan_name ?? 'Plan' }}"
                                             class="kd-plan-category-img">
                                    @else
                                        <div class="kd-plan-icon">
                                            <i class="fa-solid fa-signal"></i>
                                        </div>
                                    @endif

                                    <div class="kd-plan-text">
                                        <strong>{{ $plan->plan_name }}</strong>
                                        <span>{{ $plan->slug }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="kd-col-category" data-category="{{ $categoryName }}">
                                <span class="kd-category-pill">
                                    <i class="fa-solid fa-layer-group"></i>
                                    {{ $categoryName }}
                                </span>
                            </td>

                            <td class="kd-col-volume">
                                <span class="kd-volume-pill">
                                    <i class="fa-solid fa-database"></i>
                                    {{ $plan->data_volume }}
                                </span>
                            </td>

                            <td class="kd-col-price">
                                <span class="kd-price-pill">
                                    GHS {{ number_format((float) $plan->selling_price, 2) }}
                                </span>
                            </td>

                            <td class="kd-col-status">
                                <span class="kd-current-status {{ $isActive ? 'active' : 'inactive' }}">
                                    <i class="fa-solid {{ $isActive ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="kd-col-switch">
                                <form action="{{ route('admin.plans.status', $plan->plan_id) }}"
                                      method="POST"
                                      class="kd-status-switch-form">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden"
                                           name="status"
                                           value="{{ $isActive ? 'inactive' : 'active' }}">

                                    <button type="submit"
                                            class="kd-status-switch {{ $isActive ? 'is-active' : 'is-inactive' }}">
                                        <span class="kd-switch-track">
                                            <span class="kd-switch-dot"></span>
                                        </span>

                                        <span class="kd-switch-text">
                                            {{ $isActive ? 'Switch Off' : 'Switch On' }}
                                        </span>
                                    </button>
                                </form>
                            </td>

                            <td class="kd-col-created">
                                <span class="kd-date-pill">
                                    <i class="fa-solid fa-calendar-days"></i>
                                    {{ $plan->created_at ? \Carbon\Carbon::parse($plan->created_at)->format('d M, Y') : 'N/A' }}
                                </span>
                            </td>

                            <td class="kd-col-action">
                                <div class="kd-action-group">
                                    <button type="button"
                                            class="kd-action-btn edit edit-plan-btn"
                                            data-id="{{ $plan->plan_id }}"
                                            data-show-url="{{ route('admin.plans.show', $plan->plan_id) }}"
                                            data-update-url="{{ route('admin.plans.update', $plan->plan_id) }}"
                                            title="Edit Plan">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button type="button"
                                            class="kd-action-btn delete delete-plan-btn"
                                            data-id="{{ $plan->plan_id }}"
                                            data-url="{{ route('admin.plans.destroy', $plan->plan_id) }}"
                                            title="Delete Plan">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

</div>

@endsection

@push('modals')
    @include('admin.plans.partials.add-plan-sidebar')
    @include('admin.plans.partials.edit-plan-sidebar')
    @include('admin.plans.partials.delete-plan-modal')
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="{{ asset('admin/js/plans.js') }}?v={{ time() }}"></script>
@endpush