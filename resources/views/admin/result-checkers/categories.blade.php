@extends('admin.layouts.app')

@section('title', 'Result Checker Categories')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/result-checker-categories.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="kd-rc-page">

    <div class="kd-rc-hero">
        <div>
            <span class="kd-rc-badge">
                <i class="fa-solid fa-square-poll-vertical"></i>
                Result Checkers
            </span>

            <h1>Checker Categories</h1>
            <p>Create and manage result checker categories like WAEC, BECE, NOVDEC and other checker services.</p>
        </div>

        <button type="button" class="kd-rc-add-btn" id="openAddDrawerBtn">
            <i class="fa-solid fa-circle-plus"></i>
            Add Category
        </button>
    </div>

    @if (session('success'))
        <div class="kd-rc-alert success">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="kd-rc-alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <div class="kd-rc-stats">
        <div class="kd-rc-stat-card">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <span>Total Categories</span>
                <strong>{{ $categories->count() }}</strong>
            </div>
        </div>

        <div class="kd-rc-stat-card">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span>Active</span>
                <strong>{{ $categories->where('status', 'active')->count() }}</strong>
            </div>
        </div>

        <div class="kd-rc-stat-card">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div>
                <span>Inactive</span>
                <strong>{{ $categories->where('status', 'inactive')->count() }}</strong>
            </div>
        </div>
    </div>

    <div class="kd-rc-card">
        <div class="kd-rc-card-head">
            <div>
                <span>Categories List</span>
                <h3>All Result Checker Categories</h3>
            </div>
        </div>

        <div class="kd-rc-table-wrap">
            <table class="kd-rc-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Date Added</th>
                        <th class="kd-text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($categories as $key => $category)
                        @php
                            $categoryImageUrl = !empty($category->category_image)
                                ? asset($category->category_image)
                                : '';
                        @endphp

                        <tr>
                            <td>{{ $key + 1 }}</td>

                            <td>
                                <div class="kd-rc-img">
                                    @if (!empty($category->category_image))
                                        <img src="{{ asset($category->category_image) }}" alt="{{ $category->category_name }}">
                                    @else
                                        <i class="fa-solid fa-image"></i>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <strong>{{ $category->category_name }}</strong>
                                <small>{{ $category->category_description ?? 'No description' }}</small>
                            </td>

                            <td>
                                <span class="kd-rc-slug">{{ $category->category_slug }}</span>
                            </td>

                            <td>{{ $category->sort_order }}</td>

                            <td>
                                <span class="kd-rc-status {{ strtolower($category->status) }}">
                                    {{ ucfirst($category->status) }}
                                </span>
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($category->created_at)->format('M d, Y') }}
                            </td>

                            <td class="kd-text-right">
                                <div class="kd-rc-actions">
                                    <button type="button"
                                            class="kd-action-btn edit js-edit-category"
                                            title="Edit Category"
                                            data-id="{{ $category->category_id }}"
                                            data-name="{{ $category->category_name }}"
                                            data-description="{{ $category->category_description }}"
                                            data-sort="{{ $category->sort_order }}"
                                            data-status="{{ $category->status }}"
                                            data-image="{{ $categoryImageUrl }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form action="{{ route('admin.result-checkers.categories.status', $category->category_id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit" class="kd-action-btn status" title="Change Status">
                                            <i class="fa-solid fa-arrows-rotate"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.result-checkers.categories.destroy', $category->category_id) }}"
                                          method="POST"
                                          class="js-delete-form">
                                        @csrf
                                        @method('DELETE')

                                        <button type="button"
                                                class="kd-action-btn delete js-delete-btn"
                                                title="Delete Category"
                                                data-name="{{ $category->category_name }}">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="kd-rc-empty">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <h3>No categories found</h3>
                                    <p>Add your first result checker category.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="kd-drawer-overlay" id="categoryDrawerOverlay"></div>

<aside class="kd-category-drawer" id="categoryDrawer" aria-hidden="true">
    <div class="kd-drawer-head">
        <div>
            <span id="drawerBadge">
                <i class="fa-solid fa-layer-group"></i>
                New Category
            </span>
            <h3 id="drawerTitle">Add Category</h3>
            <p id="drawerText">Create a new result checker category.</p>
        </div>

        <button type="button" class="kd-drawer-close" id="closeDrawerBtn">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form id="categoryForm"
          action="{{ route('admin.result-checkers.categories.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="kd-drawer-form">
        @csrf

        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="kd-form-group">
            <label>Category Name</label>
            <div class="kd-input-icon">
                <i class="fa-solid fa-tag"></i>
                <input type="text"
                       name="category_name"
                       id="categoryName"
                       class="kd-uppercase"
                       required
                       placeholder="Example: WAEC">
            </div>
        </div>

        <div class="kd-form-group">
            <label>Sort Order</label>
            <div class="kd-input-icon">
                <i class="fa-solid fa-arrow-down-short-wide"></i>
                <input type="number"
                       name="sort_order"
                       id="sortOrder"
                       value="0">
            </div>
        </div>

        <div class="kd-form-group">
            <label>Description</label>
            <div class="kd-input-icon textarea">
                <i class="fa-solid fa-align-left"></i>
                <textarea name="category_description"
                          id="categoryDescription"
                          class="kd-uppercase"
                          placeholder="Optional description"></textarea>
            </div>
        </div>

        <div class="kd-form-group">
            <label>Status</label>
            <div class="kd-input-icon">
                <i class="fa-solid fa-toggle-on"></i>
                <select name="status" id="categoryStatus" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="kd-form-group" id="currentImageWrap" style="display:none;">
            <label>Current Image</label>
            <div class="kd-current-image-box">
                <img src="" alt="Current category image" id="currentCategoryImage">
            </div>
        </div>

        <div class="kd-form-group">
            <label>Category Image</label>

            <label class="kd-file-box">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <span id="fileLabelText">Choose category image</span>
                <input type="file"
                       name="category_image"
                       id="categoryImage"
                       accept="image/*">
            </label>

            <div class="kd-upload-preview" id="uploadPreviewWrap" style="display:none;">
                <img src="" alt="Selected image preview" id="uploadPreviewImage">
            </div>

            <small class="kd-help-text">
                Leave empty when editing if you do not want to change the image.
            </small>
        </div>

        <div class="kd-drawer-actions">
            <button type="button" class="kd-btn-light" id="cancelDrawerBtn">
                Cancel
            </button>

            <button type="submit" class="kd-btn-primary" id="submitBtn">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Category
            </button>
        </div>
    </form>
</aside>

<div class="kd-delete-modal" id="deleteModal">
    <div class="kd-delete-box">
        <div class="kd-delete-icon">
            <i class="fa-solid fa-trash-can"></i>
        </div>

        <h3>Delete Category?</h3>

        <p>
            You are about to delete
            <strong id="deleteCategoryName">this category</strong>.
            This action cannot be undone.
        </p>

        <div class="kd-delete-actions">
            <button type="button" class="kd-btn-light" id="cancelDeleteBtn">
                Cancel
            </button>

            <button type="button" class="kd-btn-danger" id="confirmDeleteBtn">
                <i class="fa-solid fa-trash-can"></i>
                Yes, Delete
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const drawer = document.getElementById('categoryDrawer');
    const drawerOverlay = document.getElementById('categoryDrawerOverlay');
    const openAddDrawerBtn = document.getElementById('openAddDrawerBtn');
    const closeDrawerBtn = document.getElementById('closeDrawerBtn');
    const cancelDrawerBtn = document.getElementById('cancelDrawerBtn');

    const form = document.getElementById('categoryForm');
    const methodInput = document.getElementById('formMethod');

    const drawerTitle = document.getElementById('drawerTitle');
    const drawerBadge = document.getElementById('drawerBadge');
    const drawerText = document.getElementById('drawerText');

    const categoryName = document.getElementById('categoryName');
    const categoryDescription = document.getElementById('categoryDescription');
    const sortOrder = document.getElementById('sortOrder');
    const categoryStatus = document.getElementById('categoryStatus');
    const categoryImage = document.getElementById('categoryImage');
    const fileLabelText = document.getElementById('fileLabelText');

    const currentImageWrap = document.getElementById('currentImageWrap');
    const currentCategoryImage = document.getElementById('currentCategoryImage');
    const uploadPreviewWrap = document.getElementById('uploadPreviewWrap');
    const uploadPreviewImage = document.getElementById('uploadPreviewImage');

    const deleteModal = document.getElementById('deleteModal');
    const deleteCategoryName = document.getElementById('deleteCategoryName');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    let activeDeleteForm = null;

    if (drawerOverlay && drawerOverlay.parentElement !== document.body) {
        document.body.appendChild(drawerOverlay);
    }

    if (drawer && drawer.parentElement !== document.body) {
        document.body.appendChild(drawer);
    }

    if (deleteModal && deleteModal.parentElement !== document.body) {
        document.body.appendChild(deleteModal);
    }

    function forceUppercase(element) {
        if (!element) return;

        element.addEventListener('input', function () {
            const start = this.selectionStart;
            const end = this.selectionEnd;

            this.value = this.value.toUpperCase();

            if (this.setSelectionRange) {
                this.setSelectionRange(start, end);
            }
        });
    }

    forceUppercase(categoryName);
    forceUppercase(categoryDescription);

    function resetImagePreview() {
        if (categoryImage) {
            categoryImage.value = '';
        }

        if (fileLabelText) {
            fileLabelText.innerText = 'Choose category image';
        }

        if (uploadPreviewWrap && uploadPreviewImage) {
            uploadPreviewWrap.style.display = 'none';
            uploadPreviewImage.src = '';
        }

        if (currentImageWrap && currentCategoryImage) {
            currentImageWrap.style.display = 'none';
            currentCategoryImage.src = '';
        }
    }

    function showCurrentImage(imageUrl) {
        if (!currentImageWrap || !currentCategoryImage) return;

        if (imageUrl) {
            currentCategoryImage.src = imageUrl;
            currentImageWrap.style.display = 'block';
        } else {
            currentCategoryImage.src = '';
            currentImageWrap.style.display = 'none';
        }
    }

    function lockBody() {
        document.body.classList.add('kd-panel-open');
    }

    function unlockBody() {
        const drawerIsOpen = drawer && drawer.classList.contains('show');
        const modalIsOpen = deleteModal && deleteModal.classList.contains('show');

        if (!drawerIsOpen && !modalIsOpen) {
            document.body.classList.remove('kd-panel-open');
        }
    }

    function openDrawer() {
        if (!drawer || !drawerOverlay) return;

        drawer.classList.add('show');
        drawerOverlay.classList.add('show');
        drawer.setAttribute('aria-hidden', 'false');

        lockBody();
    }

    function closeDrawer() {
        if (!drawer || !drawerOverlay) return;

        drawer.classList.remove('show');
        drawerOverlay.classList.remove('show');
        drawer.setAttribute('aria-hidden', 'true');

        unlockBody();
    }

    function openAddDrawer() {
        if (!form) return;

        form.reset();
        resetImagePreview();

        form.action = "{{ route('admin.result-checkers.categories.store') }}";
        methodInput.value = 'POST';

        drawerTitle.innerText = 'Add Category';
        drawerBadge.innerHTML = '<i class="fa-solid fa-layer-group"></i> New Category';
        drawerText.innerText = 'Create a new result checker category.';

        sortOrder.value = 0;
        categoryStatus.value = 'active';

        openDrawer();

        setTimeout(function () {
            categoryName.focus();
        }, 250);
    }

    function openEditDrawer(button) {
        if (!form || !button) return;

        const id = button.dataset.id;
        const imageUrl = button.dataset.image || '';

        form.reset();
        resetImagePreview();

        form.action = "{{ url('/admin/result-checkers/categories') }}/" + id;
        methodInput.value = 'PUT';

        drawerTitle.innerText = 'Edit Category';
        drawerBadge.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Update Category';
        drawerText.innerText = 'Update this result checker category.';

        categoryName.value = (button.dataset.name || '').toUpperCase();
        categoryDescription.value = (button.dataset.description || '').toUpperCase();
        sortOrder.value = button.dataset.sort || 0;
        categoryStatus.value = button.dataset.status || 'active';

        fileLabelText.innerText = 'Choose new image';
        showCurrentImage(imageUrl);

        openDrawer();

        setTimeout(function () {
            categoryName.focus();
        }, 250);
    }

    if (openAddDrawerBtn) {
        openAddDrawerBtn.addEventListener('click', openAddDrawer);
    }

    if (closeDrawerBtn) {
        closeDrawerBtn.addEventListener('click', closeDrawer);
    }

    if (cancelDrawerBtn) {
        cancelDrawerBtn.addEventListener('click', closeDrawer);
    }

    if (drawerOverlay) {
        drawerOverlay.addEventListener('click', closeDrawer);
    }

    document.querySelectorAll('.js-edit-category').forEach(function (button) {
        button.addEventListener('click', function () {
            openEditDrawer(this);
        });
    });

    if (categoryImage) {
        categoryImage.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];

                fileLabelText.innerText = file.name;

                if (uploadPreviewWrap && uploadPreviewImage) {
                    uploadPreviewImage.src = URL.createObjectURL(file);
                    uploadPreviewWrap.style.display = 'block';
                }
            } else {
                fileLabelText.innerText = 'Choose category image';

                if (uploadPreviewWrap && uploadPreviewImage) {
                    uploadPreviewWrap.style.display = 'none';
                    uploadPreviewImage.src = '';
                }
            }
        });
    }

    function openDeleteModal(formElement, name) {
        if (!deleteModal) return;

        activeDeleteForm = formElement;
        deleteCategoryName.innerText = name || 'this category';

        deleteModal.classList.add('show');

        lockBody();
    }

    function closeDeleteModal() {
        if (!deleteModal) return;

        activeDeleteForm = null;

        deleteModal.classList.remove('show');

        confirmDeleteBtn.disabled = false;
        confirmDeleteBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Yes, Delete';

        unlockBody();
    }

    document.querySelectorAll('.js-delete-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const deleteForm = this.closest('form');
            const category = this.dataset.name;

            openDeleteModal(deleteForm, category);
        });
    });

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            if (!activeDeleteForm) return;

            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';

            activeDeleteForm.submit();
        });
    }

    if (deleteModal) {
        deleteModal.addEventListener('click', function (event) {
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
            closeDeleteModal();
        }
    });
});
</script>
@endsection