@extends('admin.layouts.app')

@section('title', 'AFA Registrations')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="{{ asset('admin/css/afa.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('admin/css/afa-sidebar.css') }}?v={{ time() }}">
@endpush

@section('content')

<div class="kd-afa-page">

    <div class="kd-afa-header">
        <div>
            <span class="kd-page-badge">
                <i class="fa-solid fa-id-card"></i>
                AFA Management
            </span>

            <h1>
                <i class="fa-solid fa-list-check"></i>
                AFA Registrations
            </h1>

            <p>Manage all AFA registrations: upload image, set name, amount, and status.</p>
        </div>

        <button type="button" class="kd-add-afa-btn" id="openAddAfaSidebar">
            <i class="fa-solid fa-plus"></i>
            <span>Add Registration</span>
        </button>
    </div>

    <div class="kd-afa-stats">
        <div class="kd-afa-stat-card">
            <div class="kd-stat-icon total">
                <i class="fa-solid fa-list"></i>
            </div>
            <div>
                <span>Total Registrations</span>
                <strong>{{ $afas->count() }}</strong>
            </div>
        </div>

        <div class="kd-afa-stat-card active">
            <div class="kd-stat-icon active">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span>Active</span>
                <strong>{{ $afas->where('status', 'active')->count() }}</strong>
            </div>
        </div>

        <div class="kd-afa-stat-card inactive">
            <div class="kd-stat-icon inactive">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <span>Inactive</span>
                <strong>{{ $afas->where('status', 'inactive')->count() }}</strong>
            </div>
        </div>
    </div>

    <div class="kd-afa-table-card">
        <div class="kd-table-card-header">
            <div>
                <h2>
                    <i class="fa-solid fa-table-list"></i>
                    Registration Records
                </h2>
                <p>View, update, activate, deactivate, and manage AFA registrations.</p>
            </div>

            <div id="afaExportContainer" class="kd-export-container"></div>
        </div>

        <div class="kd-table-responsive">
            <table id="afaTable" class="kd-afa-table display nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Switch</th>
                        <th>Created</th>
                        <th class="no-export">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($afas as $afa)
                        @php
                            $afaId = $afa->afa_registration_id;
                            $afaName = $afa->registration_name ?? 'N/A';
                            $afaAmount = (float) ($afa->registration_amount ?? 0);
                            $afaImage = $afa->registration_image ?? null;
                            $isActive = strtolower($afa->status ?? 'inactive') === 'active';
                            $createdAt = $afa->created_at ? \Carbon\Carbon::parse($afa->created_at)->format('d M, Y') : 'N/A';

                            $afaImageUrl = null;

                            if (!empty($afaImage)) {
                                $cleanImage = ltrim($afaImage, '/');

                                if (str_starts_with($cleanImage, 'http://') || str_starts_with($cleanImage, 'https://')) {
                                    $afaImageUrl = $cleanImage;
                                } elseif (str_starts_with($cleanImage, 'storage/')) {
                                    $afaImageUrl = asset($cleanImage);
                                } elseif (str_starts_with($cleanImage, 'public/')) {
                                    $afaImageUrl = asset(str_replace('public/', 'storage/', $cleanImage));
                                } else {
                                    $afaImageUrl = asset('storage/' . $cleanImage);
                                }
                            }
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                @if(!empty($afaImageUrl))
                                    <img src="{{ $afaImageUrl }}" alt="{{ $afaName }}" class="kd-afa-thumb">
                                @else
                                    <div class="kd-afa-placeholder">
                                        <i class="fa-solid fa-id-card"></i>
                                    </div>
                                @endif
                            </td>

                            <td>{{ $afaName }}</td>

                            <td>{{ number_format($afaAmount, 2) }}</td>

                            <td>
                                <span class="{{ $isActive ? 'active' : 'inactive' }}">
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td>
                                <form action="{{ route('admin.afa.status', $afaId) }}" method="POST" class="kd-status-switch-form">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" name="status" value="{{ $isActive ? 'inactive' : 'active' }}">

                                    <button type="submit" class="kd-status-switch {{ $isActive ? 'is-active' : 'is-inactive' }}">
                                        {{ $isActive ? 'Switch Off' : 'Switch On' }}
                                    </button>
                                </form>
                            </td>

                            <td>{{ $createdAt }}</td>

                            <td>
                                <div class="kd-action-group">
                                    <button type="button"
                                            class="kd-action-btn edit edit-afa-btn"
                                            data-id="{{ $afaId }}"
                                            data-show-url="{{ route('admin.afa.show', $afaId) }}"
                                            data-update-url="{{ route('admin.afa.update', $afaId) }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button type="button"
                                            class="kd-action-btn delete delete-afa-btn"
                                            data-id="{{ $afaId }}"
                                            data-url="{{ route('admin.afa.destroy', $afaId) }}">
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

@include('admin.afa.partials.add-afa-sidebar')
@include('admin.afa.partials.edit-afa-sidebar')
@include('admin.afa.partials.delete-afa-modal')

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="{{ asset('admin/js/afa.js') }}?v={{ time() }}"></script>
@endpush