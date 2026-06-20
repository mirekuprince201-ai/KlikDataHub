document.addEventListener('DOMContentLoaded', function () {
    const config = window.KD_RESULT_CHECKER_CONFIG || {};

    const form = document.getElementById('kdResultCheckerForm');
    const toast = document.getElementById('kdCheckerToast');

    const checkoutSidebar = document.getElementById('kdCheckoutSidebar');
    const checkoutOverlay = document.getElementById('kdCheckoutOverlay');
    const checkoutClose = document.getElementById('kdCheckoutClose');
    const openCheckoutBtn = document.getElementById('kdOpenCheckoutBtn');
    const checkoutForm = document.getElementById('kdCheckoutForm');
    const checkoutAlert = document.getElementById('kdCheckoutAlert');

    if (!form) return;

    const radios = form.querySelectorAll('input[name="checker_id"]');
    const quantityInput = document.getElementById('quantity');
    const stockBox = document.getElementById('kdAvailableStock');
    const unitPriceBox = document.getElementById('kdUnitPrice');
    const totalAmountBox = document.getElementById('kdTotalAmount');
    const selectedNote = document.getElementById('kdSelectedNote');

    const sidebarType = document.getElementById('kdSidebarType');
    const sidebarCheckerName = document.getElementById('kdSidebarCheckerName');
    const sidebarQuantity = document.getElementById('kdSidebarQuantity');
    const sidebarTotal = document.getElementById('kdSidebarTotal');

    const checkoutCheckerId = document.getElementById('kdCheckoutCheckerId');
    const checkoutQuantity = document.getElementById('kdCheckoutQuantity');
    const checkoutUnitPrice = document.getElementById('kdCheckoutUnitPrice');
    const checkoutTotalAmount = document.getElementById('kdCheckoutTotalAmount');

    const customerName = document.getElementById('kdCustomerName');
    const customerEmail = document.getElementById('kdCustomerEmail');
    const customerPhone = document.getElementById('kdCustomerPhone');

    const nameError = document.getElementById('kdNameError');
    const emailError = document.getElementById('kdEmailError');
    const phoneError = document.getElementById('kdPhoneError');

    let currentTotal = 0;
    let currentUnitPrice = 0;
    let currentQuantity = 1;
    let isProcessing = false;

    function csrfToken() {
        return config.csrfToken ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            '';
    }

    function showToast(message, type = 'success') {
        if (!toast || !message) return;

        const icon = type === 'success'
            ? 'fa-circle-check'
            : type === 'warning'
                ? 'fa-circle-exclamation'
                : 'fa-triangle-exclamation';

        toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${escapeHtml(message)}</span>`;
        toast.className = `kd-checker-toast ${type} show`;

        clearTimeout(window.kdCheckerToastTimer);
        window.kdCheckerToastTimer = setTimeout(function () {
            toast.classList.remove('show');
        }, 4200);
    }

    function setAlert(message, type = 'success') {
        if (!checkoutAlert) return;
        checkoutAlert.className = `kd-checkout-alert ${type}`;
        checkoutAlert.textContent = message || '';
    }

    function setLoading(state, message = 'Processing your order. Please wait...') {
        isProcessing = state;

        const submitBtn = checkoutForm ? checkoutForm.querySelector('[type="submit"]') : null;

        if (submitBtn) {
            submitBtn.disabled = state;
            submitBtn.dataset.originalText = submitBtn.dataset.originalText || submitBtn.innerHTML;
            submitBtn.innerHTML = state
                ? '<i class="fa-solid fa-spinner fa-spin"></i> Processing...'
                : submitBtn.dataset.originalText;
        }

        if (state) {
            setAlert(message, 'success');
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function money(value) {
        return 'GH₵' + Number(value || 0).toFixed(2);
    }

    function getSelectedChecker() {
        return form.querySelector('input[name="checker_id"]:checked');
    }

    function getSelectedPayment() {
        const payment = checkoutForm ? checkoutForm.querySelector('input[name="payment_method"]:checked') : null;
        return payment ? payment.value : 'paystack';
    }

    function getDiscountedPrice(basePrice, quantity, discounts) {
        let finalPrice = Number(basePrice || 0);

        discounts.forEach(function (discount) {
            const min = Number(discount.min || 0);
            const max = Number(discount.max || 0);
            const price = Number(discount.price || 0);

            if (quantity >= min && quantity <= max && price > 0) {
                finalPrice = price;
            }
        });

        return finalPrice;
    }

    function updateAmount(showChangeToast = false) {
        const selected = getSelectedChecker();

        if (!selected) {
            stockBox.textContent = '0';
            unitPriceBox.textContent = money(0);
            totalAmountBox.textContent = money(0);
            selectedNote.textContent = 'Select a checker type to continue.';
            openCheckoutBtn.disabled = true;
            return;
        }

        const name = selected.dataset.name || 'Result Checker';
        const basePrice = Number(selected.dataset.price || 0);
        const stock = Number(selected.dataset.stock || 0);
        let quantity = Number(quantityInput.value || 1);

        let discounts = [];

        try {
            discounts = JSON.parse(selected.dataset.discounts || '[]');
        } catch (error) {
            discounts = [];
        }

        if (quantity < 1) quantity = 1;

        if (stock > 0 && quantity > stock) {
            quantity = stock;
            showToast('Quantity reduced to available stock.', 'warning');
        }

        quantityInput.value = quantity;
        quantityInput.max = stock;

        currentUnitPrice = getDiscountedPrice(basePrice, quantity, discounts);
        currentQuantity = quantity;
        currentTotal = currentUnitPrice * quantity;

        stockBox.textContent = stock.toLocaleString();
        unitPriceBox.textContent = money(currentUnitPrice);
        totalAmountBox.textContent = money(currentTotal);

        selectedNote.textContent = `${name} selected. Quantity ${quantity} × ${money(currentUnitPrice)}.`;

        openCheckoutBtn.disabled = stock <= 0;

        if (showChangeToast) {
            showToast('Amount updated successfully.', 'success');
        }
    }

    function openSidebar() {
        const selected = getSelectedChecker();

        if (!selected) {
            showToast('Please select a checker type first.', 'warning');
            return;
        }

        if (currentQuantity < 1 || currentTotal <= 0) {
            showToast('Please enter a valid quantity.', 'warning');
            return;
        }

        const stock = Number(selected.dataset.stock || 0);

        if (stock <= 0) {
            showToast('Selected checker is out of stock.', 'error');
            return;
        }

        const checkerName = selected.dataset.name || 'Result Checker';
        const checkerType = selected.dataset.type || 'CHECKER';

        sidebarType.textContent = checkerType.toUpperCase();
        sidebarCheckerName.textContent = checkerName;
        sidebarQuantity.textContent = currentQuantity;
        sidebarTotal.textContent = money(currentTotal);

        checkoutCheckerId.value = selected.value;
        checkoutQuantity.value = currentQuantity;
        checkoutUnitPrice.value = currentUnitPrice;
        checkoutTotalAmount.value = currentTotal;

        checkoutSidebar.classList.add('show');
        checkoutOverlay.classList.add('show');
        document.body.classList.add('kd-sidebar-open');
    }

    function closeSidebar() {
        if (isProcessing) return;

        checkoutSidebar.classList.remove('show');
        checkoutOverlay.classList.remove('show');
        document.body.classList.remove('kd-sidebar-open');
    }

    function setError(element, message) {
        if (!element) return;
        element.textContent = message || '';
    }

    function validateGuestFields() {
        let valid = true;

        setError(nameError, '');
        setError(emailError, '');
        setError(phoneError, '');

        if (!config.isLoggedIn) {
            const name = customerName ? customerName.value.trim() : '';
            const email = customerEmail ? customerEmail.value.trim() : '';
            const phone = customerPhone ? customerPhone.value.trim() : '';

            if (!name) {
                setError(nameError, 'Full name is required.');
                valid = false;
            }

            if (!email) {
                setError(emailError, 'Email address is required.');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                setError(emailError, 'Enter a valid email address.');
                valid = false;
            }

            if (!phone) {
                setError(phoneError, 'Phone number is required.');
                valid = false;
            } else if (!/^[0-9]{10}$/.test(phone)) {
                setError(phoneError, 'Phone number must be exactly 10 digits.');
                valid = false;
            }
        }

        return valid;
    }

    function payload() {
        return {
            checker_id: checkoutCheckerId.value,
            quantity: Number(checkoutQuantity.value || currentQuantity),
            customer_name: customerName ? customerName.value.trim() : '',
            customer_email: customerEmail ? customerEmail.value.trim() : '',
            customer_phone: customerPhone ? customerPhone.value.trim() : '',
        };
    }

    async function postJson(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data),
        });

        const json = await response.json().catch(function () {
            return {
                status: false,
                message: 'Invalid server response.',
            };
        });

        if (!response.ok || json.status === false) {
            throw new Error(json.message || 'Request failed. Please try again.');
        }

        return json;
    }

    async function payWithWallet() {
        if (!config.isLoggedIn) {
            throw new Error('Please login to pay with wallet.');
        }

        if (Number(config.walletBalance || 0) < currentTotal) {
            throw new Error('Insufficient wallet balance. Please use Paystack or top up your wallet.');
        }

        const url = config.walletUrl || config.payWithWalletUrl;

        if (!url) {
            throw new Error('Wallet payment route is not configured.');
        }

        setLoading(true, 'Processing wallet payment...');

        const result = await postJson(url, payload());

        showToast(result.message || 'Wallet payment successful.', 'success');

        if (result.redirect_url) {
            window.location.href = result.redirect_url;
        }
    }

    async function payWithPaystackPopup() {
        if (!config.initializeUrl) {
            throw new Error('Paystack initialize route is not configured.');
        }

        if (!config.verifyUrl) {
            throw new Error('Paystack verify route is not configured.');
        }

        if (!window.PaystackPop) {
            throw new Error('Paystack Popup JS is not loaded. Add https://js.paystack.co/v2/inline.js');
        }

        setLoading(true, 'Initializing secure Paystack payment...');

        const init = await postJson(config.initializeUrl, payload());

        if (!init.access_code || !init.reference) {
            throw new Error('Payment access code was not returned.');
        }

        setAlert('Paystack popup opened. Complete payment to receive your PIN.', 'success');

        const popup = new PaystackPop();

        popup.resumeTransaction(init.access_code, {
            onSuccess: async function () {
                try {
                    setLoading(true, 'Verifying payment and delivering voucher...');
                    const verified = await postJson(config.verifyUrl, {
                        reference: init.reference,
                    });

                    showToast(verified.message || 'Payment verified successfully.', 'success');

                    if (verified.redirect_url) {
                        window.location.href = verified.redirect_url;
                    }
                } catch (error) {
                    setLoading(false);
                    setAlert(error.message, 'error');
                    showToast(error.message, 'error');
                }
            },

            onCancel: function () {
                setLoading(false);
                setAlert('Payment was cancelled. No money has been deducted.', 'error');
                showToast('Payment cancelled.', 'warning');
            },

            onError: function () {
                setLoading(false);
                setAlert('Paystack payment failed. Please try again.', 'error');
                showToast('Paystack payment failed.', 'error');
            },
        });
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            updateAmount(true);
        });
    });

    quantityInput.addEventListener('input', function () {
        updateAmount(false);
    });

    quantityInput.addEventListener('change', function () {
        updateAmount(true);
    });

    if (customerPhone) {
        customerPhone.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });
    }

    if (customerName) {
        customerName.addEventListener('input', function () {
            this.value = this.value.replace(/\s{2,}/g, ' ');
        });
    }

    if (openCheckoutBtn) {
        openCheckoutBtn.addEventListener('click', openSidebar);
    }

    if (checkoutClose) {
        checkoutClose.addEventListener('click', closeSidebar);
    }

    if (checkoutOverlay) {
        checkoutOverlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    checkoutForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isProcessing) return;

        const selected = getSelectedChecker();

        if (!selected) {
            showToast('Please select a checker type first.', 'warning');
            return;
        }

        if (!validateGuestFields()) {
            setAlert('Please correct the highlighted fields.', 'error');
            return;
        }

        const payment = getSelectedPayment();

        try {
            if (payment === 'wallet') {
                await payWithWallet();
                return;
            }

            await payWithPaystackPopup();
        } catch (error) {
            setLoading(false);
            setAlert(error.message, 'error');
            showToast(error.message, 'error');
        }
    });

    const firstEnabled = form.querySelector('input[name="checker_id"]:not(:disabled)');

    if (firstEnabled && !getSelectedChecker()) {
        firstEnabled.checked = true;
    }

    updateAmount();
});