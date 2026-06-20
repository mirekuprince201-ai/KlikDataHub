@php
    $isEdit = !empty($checker);
    $formKey = $checker->checker_id ?? 'new';

    $currentDiscounts = collect($checkerDiscounts ?? []);

    if ($currentDiscounts->isEmpty()) {
        $currentDiscounts = collect([
            (object) [
                'min_quantity' => '',
                'max_quantity' => '',
                'unit_price' => '',
            ],
        ]);
    }
@endphp

<div class="kd-checker-form">

    <div class="kd-form-grid">

        <div class="kd-form-group">
            <label>Category</label>
            <select name="category_id" required>
                <option value="">Select Category</option>

                @foreach ($categories as $category)
                    <option value="{{ $category->category_id }}"
                        @selected(old('category_id', $checker->category_id ?? '') == $category->category_id)>
                        {{ $category->category_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="kd-form-group">
            <label>Checker Type</label>
            <select name="checker_type" required>
                <option value="">Select Type</option>

                @foreach ($checkerTypes as $type)
                    <option value="{{ $type }}"
                        @selected(old('checker_type', $checker->checker_type ?? '') == $type)>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="kd-form-group">
            <label>Buying Price</label>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="buying_price"
                   value="{{ old('buying_price', $checker->buying_price ?? 0) }}"
                   required>
        </div>

        <div class="kd-form-group">
            <label>Selling Price</label>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="selling_price"
                   value="{{ old('selling_price', $checker->selling_price ?? '') }}"
                   required>
        </div>

        <div class="kd-form-group">
            <label>Status</label>
            <select name="status" required>
                <option value="active" @selected(old('status', $checker->status ?? 'active') == 'active')>
                    Active
                </option>

                <option value="inactive" @selected(old('status', $checker->status ?? '') == 'inactive')>
                    Inactive
                </option>
            </select>
        </div>

        <div class="kd-form-group kd-full">
            <div class="kd-section-title">
                <span>Bulk Pricing</span>
                <h4>Discount Rules</h4>
                <p>Example: 5 to 9 at GHS 19, 10 and above at GHS 18.</p>
            </div>

            <div class="kd-discount-repeater" data-kd-discount-repeater>
                @foreach ($currentDiscounts as $discount)
                    <div class="kd-discount-row">
                        <input type="number"
                               name="discount_min_quantities[]"
                               min="1"
                               placeholder="Min Qty"
                               value="{{ old('discount_min_quantities.' . $loop->index, $discount->min_quantity ?? '') }}">

                        <input type="number"
                               name="discount_max_quantities[]"
                               min="1"
                               placeholder="Max Qty / blank for above"
                               value="{{ old('discount_max_quantities.' . $loop->index, $discount->max_quantity ?? '') }}">

                        <input type="number"
                               name="discount_unit_prices[]"
                               step="0.01"
                               min="0"
                               placeholder="Unit Price"
                               value="{{ old('discount_unit_prices.' . $loop->index, $discount->unit_price ?? '') }}">

                        <button type="button" class="kd-remove-discount" data-kd-remove-discount>
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                @endforeach
            </div>

            <button type="button" class="kd-add-discount" data-kd-add-discount>
                <i class="fa-solid fa-plus"></i>
                Add Discount Rule
            </button>
        </div>

        <div class="kd-form-group kd-full">
            <label>Voucher Entry Method</label>

            <div class="kd-entry-tabs">
                <label>
                    <input type="radio" name="voucher_entry_method" value="manual" checked>
                    <span><i class="fa-solid fa-keyboard"></i> Type Manually</span>
                </label>

                <label>
                    <input type="radio" name="voucher_entry_method" value="excel">
                    <span><i class="fa-solid fa-file-excel"></i> Upload Excel</span>
                </label>
            </div>
        </div>

        <div class="kd-form-group kd-full kd-manual-area">
            <label>Manual Serial Numbers & PINs</label>

            <div class="kd-voucher-repeater" data-kd-voucher-repeater>
                <div class="kd-voucher-row">
                    <input type="text"
                           name="serial_numbers[]"
                           class="kd-uppercase"
                           placeholder="Serial Number">

                    <input type="text"
                           name="voucher_pins[]"
                           placeholder="PIN / Password">

                    <button type="button" class="kd-remove-voucher" data-kd-remove-voucher>
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <button type="button" class="kd-add-voucher" data-kd-add-voucher>
                <i class="fa-solid fa-plus"></i>
                Add Another Serial & PIN
            </button>
        </div>

        <div class="kd-form-group kd-full kd-excel-area" style="display:none;">
            <div class="kd-upload-top">
                <label>Upload Excel File</label>

                <a href="{{ route('admin.result-checkers.vouchers.template') }}" class="kd-template-btn">
                    <i class="fa-solid fa-download"></i>
                    Download Template
                </a>
            </div>

            <div class="kd-upload-box">
                <input type="file"
                       name="voucher_file"
                       id="voucher_file_{{ $formKey }}"
                       accept=".xlsx,.xls,.csv">

                <label for="voucher_file_{{ $formKey }}" class="kd-upload-label">
                    <i class="fa-solid fa-file-excel"></i>
                    <strong>Upload Excel File</strong>
                    <span>Column A: Serial Number, Column B: PIN / Password</span>
                </label>
            </div>
        </div>

        <div class="kd-form-group kd-full">
            <label>Description</label>
            <textarea name="description"
                      class="kd-uppercase"
                      placeholder="Optional description">{{ old('description', $checker->description ?? '') }}</textarea>
        </div>

    </div>

    <div class="kd-sidebar-actions">
        <button type="button" class="kd-btn-light" data-kd-close-sidebar>
            <i class="fa-solid fa-xmark"></i>
            Cancel
        </button>

        <button type="submit" class="kd-btn-primary">
            <i class="fa-solid fa-floppy-disk"></i>
            {{ $buttonText }}
        </button>
    </div>

</div>