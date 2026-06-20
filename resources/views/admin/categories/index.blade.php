@extends('admin.layouts.app')

@section('title', 'Categories')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="{{ asset('admin/css/categories.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('admin/css/category-sidebar.css') }}?v={{ time() }}">
@endpush

@section('content')

<div class="kd-category-page">

    <div class="kd-category-header">
        <div>
            <span class="kd-page-badge">
                <i class="fa-solid fa-shapes"></i>
                Category Management
            </span>

            <h1>
                <i class="fa-solid fa-layer-group"></i>
                Categories
            </h1>

            <p>Manage all data bundle categories, network providers, and plan groups.</p>
        </div>

        <button type="button" class="kd-add-category-btn" id="openAddCategorySidebar">
            <i class="fa-solid fa-plus"></i>
            <span>Add Category</span>
        </button>
    </div>

    <div class="kd-category-stats">
        <div class="kd-category-stat-card">
            <div class="kd-stat-icon total">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <span>Total Categories</span>
                <strong>{{ $categories->count() }}</strong>
            </div>
        </div>

        <div class="kd-category-stat-card">
            <div class="kd-stat-icon active">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span>Active Categories</span>
                <strong>{{ $categories->where('status', 'active')->count() }}</strong>
            </div>
        </div>

        <div class="kd-category-stat-card">
            <div class="kd-stat-icon inactive">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <span>Inactive Categories</span>
                <strong>{{ $categories->where('status', 'inactive')->count() }}</strong>
            </div>
        </div>
    </div>

    <div class="kd-category-table-card">

        <div class="kd-table-card-header">
            <div>
                <h2>
                    <i class="fa-solid fa-table-list"></i>
                    Category Records
                </h2>
                <p>View, update, activate, deactivate, and manage category records.</p>
            </div>

            <div id="categoryExportContainer" class="kd-export-container"></div>
        </div>

        <div class="kd-table-responsive">
            <table id="categoryTable" class="kd-category-table display nowrap">
                <thead>
                    <tr>
                        <th class="kd-col-no">#</th>
                        <th class="kd-col-category">Category</th>
                        <th class="kd-col-slug">Slug</th>
                        <th class="kd-col-status">Current Status</th>
                        <th class="kd-col-switch no-export">Status Switch</th>
                        <th class="kd-col-sort">Sort</th>
                        <th class="kd-col-created">Created</th>
                        <th class="kd-col-action no-export">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($categories as $category)
                        @php
                            $isActive = strtolower($category->status ?? '') === 'active';
                        @endphp

                        <tr>
                            <td class="kd-col-no">{{ $loop->iteration }}</td>

                            <td class="kd-col-category">
                                <div class="kd-category-info">
                                    @if(!empty($category->category_image))
                                        <img src="{{ asset($category->category_image) }}"
                                             alt="{{ $category->category_name }}"
                                             class="kd-category-thumb">
                                    @else
                                        <div class="kd-category-placeholder">
                                            <i class="fa-solid fa-shapes"></i>
                                        </div>
                                    @endif

                                    <div class="kd-category-text">
                                        <strong>
                                            <i class="fa-solid fa-folder-tree"></i>
                                            {{ $category->category_name }}
                                        </strong>
                                        <span>{{ $category->category_description ?? 'No description provided' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="kd-col-slug">
                                <span class="kd-slug-pill">
                                    <i class="fa-solid fa-link"></i>
                                    {{ $category->category_slug }}
                                </span>
                            </td>

                            <td class="kd-col-status">
                                <span class="kd-current-status {{ $isActive ? 'active' : 'inactive' }}">
                                    @if($isActive)
                                        <i class="fa-solid fa-circle-check"></i>
                                        Active
                                    @else
                                        <i class="fa-solid fa-circle-xmark"></i>
                                        Inactive
                                    @endif
                                </span>
                            </td>

                            <td class="kd-col-switch">
                                <form action="{{ route('admin.categories.status', $category->category_id) }}"
                                      method="POST"
                                      class="kd-status-switch-form">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden"
                                           name="status"
                                           value="{{ $isActive ? 'inactive' : 'active' }}">

                                    <button type="submit"
                                            class="kd-status-switch {{ $isActive ? 'is-active' : 'is-inactive' }}"
                                            title="{{ $isActive ? 'Click to deactivate' : 'Click to activate' }}">
                                        <span class="kd-switch-track">
                                            <span class="kd-switch-dot"></span>
                                        </span>

                                        <span class="kd-switch-text">
                                            {{ $isActive ? 'Switch Off' : 'Switch On' }}
                                        </span>
                                    </button>
                                </form>
                            </td>

                            <td class="kd-col-sort">
                                <span class="kd-sort-pill">
                                    <i class="fa-solid fa-arrow-down-wide-short"></i>
                                    {{ $category->sort_order ?? 0 }}
                                </span>
                            </td>

                            <td class="kd-col-created">
                                <span class="kd-date-pill">
                                    <i class="fa-solid fa-calendar-days"></i>
                                    {{ $category->created_at ? \Carbon\Carbon::parse($category->created_at)->format('d M, Y') : 'N/A' }}
                                </span>
                            </td>

                            <td class="kd-col-action">
                                <div class="kd-action-group">
                                    <button type="button"
                                            class="kd-action-btn edit edit-category-btn"
                                            data-id="{{ $category->category_id }}"
                                            data-show-url="{{ route('admin.categories.show', $category->category_id) }}"
                                            data-update-url="{{ route('admin.categories.update', $category->category_id) }}"
                                            title="Edit Category">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button type="button"
                                            class="kd-action-btn delete delete-category-btn"
                                            data-id="{{ $category->category_id }}"
                                            data-url="{{ route('admin.categories.destroy', $category->category_id) }}"
                                            title="Delete Category">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                <i class="fa-solid fa-circle-info"></i>
                                No category records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection

@push('modals')
    @include('admin.categories.partials.add-category-sidebar')
    @include('admin.categories.partials.edit-category-sidebar')
    @include('admin.categories.partials.delete-category-modal')
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="{{ asset('admin/js/categories.js') }}?v={{ time() }}"></script>
@endpush