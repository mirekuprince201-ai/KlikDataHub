'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const KD_MAX_ROWS = 2000;
    const KD_MAX_PREVIEW_ROWS = 150;
    const KD_MAX_FILE_SIZE = 10 * 1024 * 1024;

    const state = {
        categories: Array.isArray(window.KD_BULK_CATEGORIES) ? window.KD_BULK_CATEGORIES : [],
        walletBalance: Number(window.KD_WALLET_BALANCE || 0),
        selectedCategory: null,
        activeTab: 'quick',
        excelOrders: []
    };

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    const el = {
        form: $('#bulkPurchaseForm'),
        selectedCategoryId: $('#selectedCategoryId'),
        selectedCategorySlug: $('#selectedCategorySlug'),
        categoryRadios: $$('input[name="bulk_category"]'),
        plansCard: $('#plansCard'),
        plansTitle: $('#plansTitle'),
        plansGrid: $('#plansGrid'),
        tabs: $$('.kd-bulk-tab'),
        panels: { quick: $('#tabQuick'), manual: $('#tabManual'), excel: $('#tabExcel') },
        quickPasteInput: $('#quickPasteInput'),
        clearQuickPaste: $('#clearQuickPaste'),
        defaultManualPlan: $('#defaultManualPlan'),
        manualRows: $('#manualRows'),
        addManualRow: $('#addManualRow'),
        manualCount: $('#manualCount'),
        excelFile: $('#excelFile'),
        downloadTemplate: $('#downloadTemplate'),
        uploadBox: $('.kd-upload-box'),
        previewBody: $('#previewBody'),
        validCount: $('#validCount'),
        totalDeduction: $('#totalDeduction'),
        balanceAfter: $('#balanceAfter'),
        submitBulkBtn: $('#submitBulkBtn'),
        ordersPayload: $('#ordersPayload'),
        totalAmountInput: $('#totalAmountInput')
    };

    function money(amount) {
        return 'GH₵' + Number(amount || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>'"]/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
        }[ch]));
    }

    function toast(message, type = 'info', timeout = 4200) {
        let wrap = $('.kd-toast-wrap');

        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'kd-toast-wrap';
            document.body.appendChild(wrap);
        }

        const icon = type === 'success' ? 'fa-circle-check'
            : type === 'error' ? 'fa-circle-xmark'
            : type === 'warning' ? 'fa-triangle-exclamation'
            : 'fa-circle-info';

        const node = document.createElement('div');
        node.className = `kd-toast kd-toast-${type}`;
        node.innerHTML = `
            <i class="fa-solid ${icon}"></i>
            <span>${esc(message)}</span>
            <button type="button" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;

        wrap.appendChild(node);
        requestAnimationFrame(() => node.classList.add('show'));

        const remove = () => {
            node.classList.remove('show');
            setTimeout(() => node.remove(), 220);
        };

        node.querySelector('button').addEventListener('click', remove);
        setTimeout(remove, timeout);
    }

    function confirmModal(options = {}) {
        const title = options.title || 'Confirm Action';
        const message = options.message || 'Do you want to continue?';
        const confirmText = options.confirmText || 'Confirm';
        const cancelText = options.cancelText || 'Cancel';
        const tone = options.tone || 'primary';

        return new Promise(resolve => {
            const old = $('.kd-confirm-modal');
            if (old) old.remove();

            const modal = document.createElement('div');
            modal.className = 'kd-confirm-modal';
            modal.innerHTML = `
                <div class="kd-confirm-backdrop"></div>
                <div class="kd-confirm-dialog" role="dialog" aria-modal="true">
                    <div class="kd-confirm-icon kd-confirm-${esc(tone)}">
                        <i class="fa-solid ${tone === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-question'}"></i>
                    </div>
                    <h3>${esc(title)}</h3>
                    <p>${esc(message)}</p>
                    <div class="kd-confirm-actions">
                        <button type="button" class="kd-confirm-cancel">${esc(cancelText)}</button>
                        <button type="button" class="kd-confirm-ok kd-confirm-${esc(tone)}">${esc(confirmText)}</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            requestAnimationFrame(() => modal.classList.add('show'));

            const close = value => {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 180);
                resolve(value);
            };

            $('.kd-confirm-ok', modal).addEventListener('click', () => close(true));
            $('.kd-confirm-cancel', modal).addEventListener('click', () => close(false));
            $('.kd-confirm-backdrop', modal).addEventListener('click', () => close(false));
        });
    }

    function debounce(fn, delay = 180) {
        let timer = null;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    function normalizeCapacity(value) {
        return String(value || '').trim().toUpperCase().replace(/\s+/g, '').replace(/GB$/i, '');
    }

    function normalizePhone(phone) {
        let clean = String(phone || '').trim().replace(/[^0-9+]/g, '');

        if (clean.startsWith('+233')) clean = '0' + clean.slice(4);
        if (clean.startsWith('233')) clean = '0' + clean.slice(3);

        clean = clean.replace(/\D+/g, '');

        if (clean.length === 9) clean = '0' + clean;

        return clean;
    }

    function networkPrefixRule(categoryName = '', slug = '') {
        const key = `${categoryName} ${slug}`.toLowerCase();

        if (key.includes('mtn')) {
            return { name: 'MTN', prefixes: ['024', '025', '053', '054', '055', '059'] };
        }

        if (key.includes('telecel') || key.includes('vodafone')) {
            return { name: 'Telecel', prefixes: ['020', '050'] };
        }

        if (key.includes('airteltigo') || key.includes('airtel') || key.includes('tigo')) {
            return { name: 'AT', prefixes: ['026', '027', '056', '057'] };
        }

        return { name: 'Selected Network', prefixes: [] };
    }

    function validPrefix(phone) {
        if (!state.selectedCategory) return true;

        const rule = networkPrefixRule(
            state.selectedCategory.category_name,
            state.selectedCategory.category_slug
        );

        return rule.prefixes.length === 0 || rule.prefixes.some(prefix => phone.startsWith(prefix));
    }

    function plans() {
        return state.selectedCategory && Array.isArray(state.selectedCategory.plans)
            ? state.selectedCategory.plans
            : [];
    }

    function findPlan(capacity) {
        const clean = normalizeCapacity(capacity);
        return plans().find(plan => normalizeCapacity(plan.capacity) === clean);
    }

    function planText(plan) {
        return `${plan.capacity} - ${money(plan.price)}`;
    }

    function renderPlans() {
        if (!el.plansGrid || !el.defaultManualPlan) return;

        el.plansGrid.innerHTML = '';
        el.defaultManualPlan.innerHTML = '<option value="">Select default plan...</option>';

        if (!state.selectedCategory) {
            el.plansCard.style.display = 'none';
            return;
        }

        el.plansTitle.textContent = `${state.selectedCategory.category_name} Available Plans`;
        el.plansCard.style.display = 'block';

        if (plans().length === 0) {
            el.plansGrid.innerHTML = `
                <div class="kd-bulk-empty kd-full-span">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>No Plans Available</h3>
                    <p>No active package was found for this category.</p>
                </div>
            `;
            return;
        }

        plans().forEach(plan => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'kd-plan-card';
            card.dataset.capacity = plan.capacity;
            card.innerHTML = `
                <span>${esc(plan.capacity)}</span>
                <strong>${money(plan.price)}</strong>
                <em>Available</em>
            `;

            card.addEventListener('click', () => applyPlanToActive(plan.capacity));
            el.plansGrid.appendChild(card);

            const option = document.createElement('option');
            option.value = plan.capacity;
            option.textContent = planText(plan);
            el.defaultManualPlan.appendChild(option);
        });

        refreshManualSelects();
    }

    function applyPlanToActive(capacity) {
        if (state.activeTab === 'manual') {
            el.defaultManualPlan.value = capacity;

            $$('.manual-plan', el.manualRows).forEach(select => {
                select.value = capacity;
            });

            toast(`Applied ${capacity} to manual rows.`, 'success');
        } else {
            toast(`Use capacity ${String(capacity).replace(/GB/i, '')} in your ${state.activeTab === 'quick' ? 'quick paste' : 'Excel'} entries.`, 'info');
        }

        updatePreview();
    }

    function switchTab(tab) {
        state.activeTab = tab;

        el.tabs.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));

        Object.entries(el.panels).forEach(([key, panel]) => {
            if (panel) panel.classList.toggle('active', key === tab);
        });

        updatePreview();
    }

    function parseQuickPaste() {
        return String(el.quickPasteInput?.value || '')
            .split('\n')
            .map(line => line.trim())
            .filter(Boolean)
            .slice(0, KD_MAX_ROWS)
            .map(line => {
                const parts = line.split(/[\s,;\t]+/).filter(Boolean);
                return { phone: parts[0] || '', capacity: parts[1] || '' };
            });
    }

    function getManualOrders() {
        return $$('.kd-manual-row', el.manualRows).map(row => ({
            phone: $('.manual-phone', row).value,
            capacity: $('.manual-plan', row).value
        }));
    }

    function getActiveOrders() {
        if (state.activeTab === 'quick') return parseQuickPaste();
        if (state.activeTab === 'manual') return getManualOrders();
        return state.excelOrders;
    }

    function rebuildQuickPasteFromOrders(orders) {
        el.quickPasteInput.value = orders
            .map(order => `${normalizePhone(order.phone)} ${normalizeCapacity(order.capacity)}`)
            .join('\n');
    }

    function removeOrderAt(index) {
        if (state.activeTab === 'quick') {
            const orders = parseQuickPaste();
            orders.splice(index, 1);
            rebuildQuickPasteFromOrders(orders);
            toast('Order removed from quick paste.', 'success');
        }

        if (state.activeTab === 'manual') {
            const rows = $$('.kd-manual-row', el.manualRows);
            if (rows[index]) {
                if (rows.length <= 1) {
                    $('.manual-phone', rows[index]).value = '';
                    $('.manual-plan', rows[index]).value = '';
                } else {
                    rows[index].remove();
                }

                updateManualIndexes();
                toast('Manual order removed.', 'success');
            }
        }

        if (state.activeTab === 'excel') {
            state.excelOrders.splice(index, 1);
            toast('Excel order removed from preview.', 'success');
        }

        updatePreview();
    }

    function validateOrders() {
        const rawOrders = getActiveOrders();
        const usedNumbers = {};

        return rawOrders.map((order, index) => {
            const phone = normalizePhone(order.phone);
            const capacity = normalizeCapacity(order.capacity);
            const plan = findPlan(capacity);

            let valid = true;
            let status = 'Valid';

            if (!state.selectedCategory) {
                valid = false;
                status = 'Select category';
            } else if (!phone || phone.length !== 10 || !phone.startsWith('0')) {
                valid = false;
                status = 'Invalid phone';
            } else if (usedNumbers[phone]) {
                valid = false;
                status = 'Duplicate number';
            } else if (!validPrefix(phone)) {
                valid = false;
                status = 'Wrong network prefix';
            } else if (!capacity) {
                valid = false;
                status = 'Missing capacity';
            } else if (!plan) {
                valid = false;
                status = 'Plan not found';
            }

            if (phone) usedNumbers[phone] = true;

            return {
                index: index + 1,
                sourceIndex: index,
                phone,
                capacity: plan ? plan.capacity : (capacity ? capacity + 'GB' : '-'),
                rawCapacity: capacity,
                price: plan ? Number(plan.price) : 0,
                status,
                valid
            };
        });
    }

    function updatePreview() {
        const validated = validateOrders();
        const validOrders = validated.filter(order => order.valid);
        const duplicateOrders = validated.filter(order => order.status === 'Duplicate number');
        const total = validOrders.reduce((sum, order) => sum + Number(order.price || 0), 0);
        const after = state.walletBalance - total;

        el.previewBody.innerHTML = '';

        if (validated.length === 0) {
            el.previewBody.innerHTML = `
                <tr>
                    <td colspan="6" class="kd-preview-empty">
                        Select a category and enter orders to preview.
                    </td>
                </tr>
            `;
        } else {
            validated.slice(0, KD_MAX_PREVIEW_ROWS).forEach(order => {
                const tr = document.createElement('tr');
                tr.className = order.valid ? 'valid' : 'invalid';

                tr.innerHTML = `
                    <td>${order.index}</td>
                    <td>${esc(order.phone || '-')}</td>
                    <td>${esc(order.capacity)}</td>
                    <td>${order.valid ? money(order.price) : '-'}</td>
                    <td>
                        <span class="${order.valid ? 'kd-status-ok' : 'kd-status-bad'}">
                            ${esc(order.status)}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="kd-preview-remove" data-remove-index="${order.sourceIndex}" title="Remove this row">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </td>
                `;

                el.previewBody.appendChild(tr);
            });

            if (validated.length > KD_MAX_PREVIEW_ROWS) {
                el.previewBody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td colspan="6" class="kd-preview-empty">
                            Showing first ${KD_MAX_PREVIEW_ROWS} of ${validated.length} rows.
                        </td>
                    </tr>
                `);
            }
        }

        $$('.kd-preview-remove', el.previewBody).forEach(btn => {
            btn.addEventListener('click', function () {
                removeOrderAt(Number(this.dataset.removeIndex));
            });
        });

        el.validCount.textContent = `${validOrders.length} valid ${validOrders.length === 1 ? 'order' : 'orders'}`;
        el.totalDeduction.textContent = money(total);
        el.balanceAfter.textContent = money(after);
        el.balanceAfter.classList.toggle('danger', after < 0);

        el.ordersPayload.value = JSON.stringify(validOrders);
        el.totalAmountInput.value = total.toFixed(2);

        el.submitBulkBtn.disabled = !state.selectedCategory
            || validOrders.length === 0
            || duplicateOrders.length > 0
            || total <= 0
            || total > state.walletBalance;
    }

    function addManualRowValue(phone = '', capacity = '') {
        const rows = $$('.kd-manual-row', el.manualRows);

        if (rows.length >= KD_MAX_ROWS) {
            toast('Maximum 2,000 manual orders allowed.', 'warning');
            return;
        }

        const row = document.createElement('div');
        row.className = 'kd-manual-row';

        row.innerHTML = `
            <span class="manual-index"></span>
            <input type="text" class="manual-phone" placeholder="0XXXXXXXXX" value="${esc(phone)}" inputmode="numeric">
            <select class="manual-plan">
                <option value="">Select plan...</option>
            </select>
            <button type="button" class="manual-remove" title="Remove row">
                <i class="fa-regular fa-trash-can"></i>
            </button>
        `;

        el.manualRows.appendChild(row);

        const select = $('.manual-plan', row);

        plans().forEach(plan => {
            const option = document.createElement('option');
            option.value = plan.capacity;
            option.textContent = planText(plan);
            select.appendChild(option);
        });

        if (capacity) select.value = capacity;

        $('.manual-phone', row).addEventListener('input', debounce(updatePreview));
        select.addEventListener('change', updatePreview);

        $('.manual-remove', row).addEventListener('click', function () {
            if ($$('.kd-manual-row', el.manualRows).length <= 1) {
                $('.manual-phone', row).value = '';
                select.value = '';
                updatePreview();
                return;
            }

            row.remove();
            updateManualIndexes();
            updatePreview();
        });

        updateManualIndexes();
        updatePreview();
    }

    function updateManualIndexes() {
        $$('.kd-manual-row', el.manualRows).forEach((row, index) => {
            $('.manual-index', row).textContent = index + 1;
        });

        if (el.manualCount) {
            el.manualCount.textContent = `(${$$('.kd-manual-row', el.manualRows).length}/2,000)`;
        }
    }

    function refreshManualSelects() {
        $$('.kd-manual-row', el.manualRows).forEach(row => {
            const select = $('.manual-plan', row);
            const current = select.value;

            select.innerHTML = '<option value="">Select plan...</option>';

            plans().forEach(plan => {
                const option = document.createElement('option');
                option.value = plan.capacity;
                option.textContent = planText(plan);
                select.appendChild(option);
            });

            select.value = plans().some(plan => plan.capacity === current) ? current : '';
        });
    }

    function readExcel(file) {
        if (!file) return;

        if (file.size > KD_MAX_FILE_SIZE) {
            toast('File is too large. Maximum upload size is 10MB.', 'error');
            el.excelFile.value = '';
            return;
        }

        if (!window.XLSX) {
            toast('Excel library not loaded. Please refresh and try again.', 'error');
            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {
            try {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(sheet, { defval: '' });

                state.excelOrders = rows.slice(0, KD_MAX_ROWS)
                    .map(row => ({
                        phone: row.phoneNumber || row.phone || row.Phone || row.PHONE || row.Number || row.number || '',
                        capacity: row.capacity || row.Capacity || row.CAPACITY || row.GB || row.gb || ''
                    }))
                    .filter(row => row.phone || row.capacity);

                toast(`${state.excelOrders.length} rows loaded from Excel.`, 'success');
                updatePreview();
            } catch (error) {
                toast('Could not read the uploaded file. Use the template format.', 'error');
            }
        };

        reader.readAsArrayBuffer(file);
    }

    function downloadTemplateFile() {
        if (!window.XLSX) {
            toast('Excel library not loaded. Please refresh and try again.', 'error');
            return;
        }

        const data = [
            ['phoneNumber', 'capacity'],
            ['0551082710', '2'],
            ['0241234567', '5']
        ];

        const worksheet = XLSX.utils.aoa_to_sheet(data);
        const workbook = XLSX.utils.book_new();

        XLSX.utils.book_append_sheet(workbook, worksheet, 'Bulk Purchase');
        XLSX.writeFile(workbook, 'bulk-purchase-template.xlsx');
    }

    el.categoryRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            state.selectedCategory = state.categories.find(category => String(category.category_id) === String(this.value));

            el.selectedCategoryId.value = this.value;
            el.selectedCategorySlug.value = this.dataset.slug || '';

            renderPlans();
            updatePreview();

            if (state.selectedCategory) {
                toast(`${state.selectedCategory.category_name} selected.`, 'success', 2500);
            }
        });
    });

    el.tabs.forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    if (el.quickPasteInput) {
        el.quickPasteInput.addEventListener('input', debounce(updatePreview));
    }

    if (el.clearQuickPaste) {
        el.clearQuickPaste.addEventListener('click', async function () {
            if (!el.quickPasteInput.value.trim()) return;

            const ok = await confirmModal({
                title: 'Clear Quick Paste?',
                message: 'This will remove all pasted numbers from the quick paste box.',
                confirmText: 'Clear',
                tone: 'danger'
            });

            if (ok) {
                el.quickPasteInput.value = '';
                updatePreview();
                toast('Quick paste cleared.', 'success');
            }
        });
    }

    if (el.defaultManualPlan) {
        el.defaultManualPlan.addEventListener('change', function () {
            $$('.manual-plan', el.manualRows).forEach(select => {
                select.value = this.value;
            });

            updatePreview();
        });
    }

    if (el.addManualRow) {
        el.addManualRow.addEventListener('click', () => addManualRowValue());
    }

    if (el.excelFile) {
        el.excelFile.addEventListener('change', function () {
            readExcel(this.files[0]);
        });
    }

    if (el.downloadTemplate) {
        el.downloadTemplate.addEventListener('click', downloadTemplateFile);
    }

    if (el.uploadBox) {
        ['dragenter', 'dragover'].forEach(type => {
            el.uploadBox.addEventListener(type, e => {
                e.preventDefault();
                el.uploadBox.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(type => {
            el.uploadBox.addEventListener(type, e => {
                e.preventDefault();
                el.uploadBox.classList.remove('dragover');
            });
        });

        el.uploadBox.addEventListener('drop', e => {
            const file = e.dataTransfer.files[0];
            if (file) readExcel(file);
        });
    }

    if (el.form) {
        el.form.addEventListener('submit', async function (event) {
            event.preventDefault();
            updatePreview();

            const validated = validateOrders();
            const duplicateOrders = validated.filter(order => order.status === 'Duplicate number');
            const validOrders = JSON.parse(el.ordersPayload.value || '[]');
            const total = Number(el.totalAmountInput.value || 0);

            if (!state.selectedCategory) {
                toast('Please select a network category.', 'error');
                return;
            }

            if (duplicateOrders.length > 0) {
                toast('Duplicate number detected. Remove duplicate numbers before payment.', 'error');
                return;
            }

            if (validOrders.length === 0) {
                toast('Please enter at least one valid order.', 'error');
                return;
            }

            if (total > state.walletBalance) {
                toast('Insufficient wallet balance.', 'error');
                return;
            }

            const ok = await confirmModal({
                title: 'Confirm Bulk Purchase',
                message: `You are about to buy ${validOrders.length} order(s). Total deduction: ${money(total)}.`,
                confirmText: 'Pay Now',
                tone: 'primary'
            });

            if (ok) {
                el.submitBulkBtn.disabled = true;
                el.submitBulkBtn.classList.add('loading');
                el.submitBulkBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
                el.form.submit();
            }
        });
    }

    addManualRowValue();
    updatePreview();
});