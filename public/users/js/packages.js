document.addEventListener('DOMContentLoaded', function () {
    const sheet = document.getElementById('purchaseSheet');
    const backdrop = document.getElementById('purchaseBackdrop');
    const closeBtn = document.getElementById('closePurchaseSheet');

    const planId = document.getElementById('purchasePlanId');
    const dataSize = document.getElementById('purchaseDataSize');
    const amount = document.getElementById('purchaseAmount');
    const networkName = document.getElementById('purchaseNetworkName');
    const networkInput = document.getElementById('purchaseNetworkInput');
    const networkImage = document.getElementById('purchaseNetworkImage');

    const purchaseForm = document.getElementById('purchaseForm');
    const createOrderBtn = document.getElementById('createOrderBtn');
    const loginEmail = document.getElementById('loginEmail');

    const beneficiaryNumber = document.getElementById('beneficiaryNumber');
    const beneficiaryError = document.getElementById('beneficiaryError');
    const beneficiaryHint = document.getElementById('beneficiaryHint');
    const beneficiaryNumberSuggestions = document.getElementById('beneficiaryNumberSuggestions');

    const portedWrapper = document.getElementById('portedWrapper');
    const portedNumber = document.getElementById('portedNumber');

    const paymentMethodInput = document.getElementById('paymentMethodInput');
    const paymentMethodOptions = document.querySelectorAll('.kd-payment-method-option');

    const purchaseAlert = document.getElementById('purchaseAlert');

    let selectedNetwork = '';
    let lastToastPayload = null;

    const BENEFICIARY_KEY = 'kd_beneficiary_history';

    const NETWORK_PREFIXES = {
        mtn: ['024', '025', '053', '054', '055', '059'],
        telecel: ['020', '050'],
        vodafone: ['020', '050'],
        at: ['026', '027', '056', '057'],
        airteltigo: ['026', '027', '056', '057'],
        airtel: ['026', '027', '056', '057'],
        tigo: ['026', '027', '056', '057']
    };

    function cfg() {
        return window.KD_PACKAGE_PAYMENT || {};
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function onlyNumbers(value) {
        return String(value || '').replace(/[^0-9]/g, '').slice(0, 10);
    }

    function getAmountValue() {
        return parseFloat(String(amount?.textContent || '0').replace(/[^0-9.]/g, '')) || 0;
    }

    function showSidebarMessage(message, type = 'error') {
        if (!purchaseAlert) {
            alert(message);
            return;
        }

        purchaseAlert.style.display = 'flex';
        purchaseAlert.className = 'kd-purchase-alert ' + type + ' show';

        purchaseAlert.innerHTML = `
            <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i>
            <span>${escapeHtml(message)}</span>
        `;

        purchaseAlert.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

    function showOrderToast(toast) {
        const title = toast?.title || 'Order Received';
        const message = toast?.message || 'Your order has been accepted and is processing.';
        const delivery = toast?.delivery || 'Delivery: 30-60 mins';
        const buyAgain = toast?.buy_again || cfg().dashboardUrl || window.location.origin;

        if (window.Notyf) {
            const notyf = new Notyf({
                duration: 7000,
                position: { x: 'right', y: 'top' },
                dismissible: true
            });

            notyf.success(`${title}: ${message} ${delivery}`);
            return;
        }

        if (window.Swal) {
            window.Swal.fire({
                icon: 'success',
                title: title,
                html: `
                    <div style="text-align:left;line-height:1.7">
                        <strong>${escapeHtml(message)}</strong><br>
                        <span>${escapeHtml(delivery)}</span><br>
                        <span>Buy again: ${escapeHtml(buyAgain)}</span>
                    </div>
                `,
                confirmButtonText: 'Okay'
            });
            return;
        }

        showSidebarMessage(`${title}. ${message} ${delivery}. Buy again: ${buyAgain}`, 'success');
    }

    function clearSidebarMessage() {
        if (!purchaseAlert) return;

        purchaseAlert.style.display = 'none';
        purchaseAlert.className = 'kd-purchase-alert';
        purchaseAlert.innerHTML = '';
    }

    function normalizeNetwork(network) {
        const value = String(network || '').toLowerCase();

        if (value.includes('mtn')) return 'mtn';
        if (value.includes('telecel')) return 'telecel';
        if (value.includes('vodafone')) return 'telecel';
        if (value.includes('airteltigo')) return 'at';
        if (value.includes('airtel')) return 'at';
        if (value.includes('tigo')) return 'at';
        if (value === 'at') return 'at';

        return 'default';
    }

    function getNetworkLabel(networkKey) {
        if (networkKey === 'mtn') return 'MTN';
        if (networkKey === 'telecel') return 'TELECEL';
        if (networkKey === 'at') return 'AT';
        return 'selected network';
    }

    function clearFieldError(input, errorElement) {
        if (input) input.classList.remove('is-invalid', 'is-valid');

        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.remove('show');
        }
    }

    function setFieldError(input, errorElement, message) {
        if (input) {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }

        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }

        showSidebarMessage(message, 'error');
    }

    function setFieldSuccess(input) {
        if (input) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        }
    }

    function shouldShowPorted(number) {
        const networkKey = normalizeNetwork(selectedNetwork);
        const allowedPrefixes = NETWORK_PREFIXES[networkKey] || [];

        if (!number || number.length < 3 || !allowedPrefixes.length) {
            return false;
        }

        return !allowedPrefixes.includes(number.substring(0, 3));
    }

    function updatePortedVisibility(number) {
        if (!portedWrapper || !portedNumber) return;

        if (shouldShowPorted(number)) {
            portedWrapper.classList.add('show');
        } else {
            portedWrapper.classList.remove('show');
            portedNumber.checked = false;
        }
    }

    function validateBeneficiary(showError = true) {
        if (!beneficiaryNumber) return true;

        const number = onlyNumbers(beneficiaryNumber.value);
        beneficiaryNumber.value = number;

        clearFieldError(beneficiaryNumber, beneficiaryError);
        updatePortedVisibility(number);

        if (beneficiaryHint) {
            beneficiaryHint.textContent = 'Number to receive the data.';
        }

        if (!number) {
            if (showError) {
                setFieldError(beneficiaryNumber, beneficiaryError, 'Beneficiary number is required.');
            }
            return false;
        }

        if (number.length !== 10) {
            if (showError) {
                setFieldError(beneficiaryNumber, beneficiaryError, 'Beneficiary number must be 10 digits.');
            }
            return false;
        }

        const networkKey = normalizeNetwork(selectedNetwork);
        const prefix = number.substring(0, 3);
        const allowedPrefixes = NETWORK_PREFIXES[networkKey] || [];
        const isPorted = portedNumber && portedNumber.checked;

        if (!isPorted && allowedPrefixes.length && !allowedPrefixes.includes(prefix)) {
            if (showError) {
                setFieldError(
                    beneficiaryNumber,
                    beneficiaryError,
                    `Prefix does not match ${getNetworkLabel(networkKey)}. Tick Ported if needed.`
                );
            }
            return false;
        }

        setFieldSuccess(beneficiaryNumber);

        if (beneficiaryHint) {
            beneficiaryHint.textContent = isPorted ? 'Ported number accepted.' : 'Number looks good.';
        }

        return true;
    }

    function getSavedBeneficiaries() {
        try {
            return JSON.parse(localStorage.getItem(BENEFICIARY_KEY)) || [];
        } catch (e) {
            return [];
        }
    }

    function saveBeneficiary(value) {
        value = onlyNumbers(value);
        if (!value || value.length !== 10) return;

        let items = getSavedBeneficiaries();
        items = items.filter(item => item !== value);
        items.unshift(value);
        items = items.slice(0, 8);

        localStorage.setItem(BENEFICIARY_KEY, JSON.stringify(items));
    }

    function renderBeneficiarySuggestions() {
        if (!beneficiaryNumberSuggestions || !beneficiaryNumber) return;

        const current = onlyNumbers(beneficiaryNumber.value);
        let values = getSavedBeneficiaries();

        if (current) {
            values = values.filter(value => value.includes(current));
        }

        beneficiaryNumberSuggestions.innerHTML = '';

        if (!values.length) {
            beneficiaryNumberSuggestions.classList.remove('show');
            return;
        }

        values.slice(0, 5).forEach(value => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'kd-suggestion-chip';
            button.textContent = value;

            button.addEventListener('click', function () {
                beneficiaryNumber.value = value;
                beneficiaryNumberSuggestions.classList.remove('show');
                validateBeneficiary(true);
            });

            beneficiaryNumberSuggestions.appendChild(button);
        });

        beneficiaryNumberSuggestions.classList.add('show');
    }

    function hideBeneficiarySuggestions() {
        setTimeout(function () {
            if (beneficiaryNumberSuggestions) {
                beneficiaryNumberSuggestions.classList.remove('show');
            }
        }, 180);
    }

    function selectPaymentMethod(method) {
        method = method === 'wallet' ? 'wallet' : 'paystack';

        if (paymentMethodInput) {
            paymentMethodInput.value = method;
        }

        paymentMethodOptions.forEach(option => {
            const optionMethod = option.dataset.paymentMethod;
            const radio = option.querySelector('input[type="radio"]');

            option.classList.toggle('active', optionMethod === method);

            if (radio) {
                radio.checked = optionMethod === method;
            }
        });
    }

    function resetSheetState() {
        lastToastPayload = null;
        clearSidebarMessage();

        if (portedWrapper) portedWrapper.classList.remove('show');
        if (portedNumber) portedNumber.checked = false;

        if (beneficiaryHint) {
            beneficiaryHint.textContent = 'Number to receive the data.';
        }

        clearFieldError(beneficiaryNumber, beneficiaryError);
        selectPaymentMethod('paystack');
        setButtonLoading(false);
    }

    function openSheet(button) {
        selectedNetwork = button.dataset.packageNetwork || '';

        if (planId) planId.value = button.dataset.packageId || '';
        if (dataSize) dataSize.textContent = button.dataset.packageVolume || '--';
        if (amount) amount.textContent = button.dataset.packagePrice || '0.00';
        if (networkName) networkName.textContent = selectedNetwork;
        if (networkInput) networkInput.value = selectedNetwork;
        if (networkImage && button.dataset.packageImage) networkImage.src = button.dataset.packageImage;

        resetSheetState();

        if (sheet) sheet.classList.add('show');
        if (backdrop) backdrop.classList.add('show');

        document.body.classList.add('kd-sheet-open');

        setTimeout(function () {
            if (beneficiaryNumber) beneficiaryNumber.focus();
        }, 250);
    }

    function closeSheet() {
        if (sheet) sheet.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');

        document.body.classList.remove('kd-sheet-open');

        if (beneficiaryNumberSuggestions) {
            beneficiaryNumberSuggestions.classList.remove('show');
        }
    }

    function setButtonLoading(isLoading, text = 'Create Order') {
        if (!createOrderBtn) return;

        createOrderBtn.disabled = isLoading;
        createOrderBtn.innerHTML = isLoading
            ? `<i class="fa-solid fa-spinner fa-spin"></i><span>${escapeHtml(text)}</span>`
            : `<i class="fa-solid fa-lock"></i><span>Create Order</span>`;
    }

    function getFirstValidationError(data) {
        if (!data || !data.errors) return null;

        const firstError = Object.values(data.errors)[0];

        if (Array.isArray(firstError)) {
            return firstError[0];
        }

        return firstError || null;
    }

    async function postJson(url, payload) {
        if (!url) {
            throw new Error('Request URL is missing.');
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': cfg().csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.status === false) {
            throw new Error(
                getFirstValidationError(data) ||
                data.message ||
                data.error ||
                `Request failed with status ${response.status}.`
            );
        }

        return data;
    }

    async function initializePaystack() {
        const payload = {
            plan_id: planId ? planId.value : '',
            network_name: networkInput ? networkInput.value : '',
            recipient_phone: beneficiaryNumber ? beneficiaryNumber.value : '',
            is_ported: portedNumber && portedNumber.checked ? 1 : 0,
            email: loginEmail ? loginEmail.value : ''
        };

        return await postJson(cfg().paystackInitializeUrl, payload);
    }

    async function verifyPaystack(reference) {
        return await postJson(cfg().paystackVerifyUrl, {
            reference: reference
        });
    }

    function redirectToDashboard(delay = 1500) {
        setTimeout(function () {
            window.location.href = cfg().dashboardUrl || window.location.href;
        }, delay);
    }

    function openPaystackCheckout(initData) {
        const publicKey = initData.public_key;
        const email = initData.email;
        const payAmount = parseInt(initData.amount, 10);
        const reference = initData.reference;
        const authorizationUrl = initData.authorization_url;

        lastToastPayload = initData.toast || null;

        if (!publicKey) {
            throw new Error('Paystack public key is missing.');
        }

        if (!email) {
            throw new Error('Customer email is missing.');
        }

        if (!payAmount || payAmount < 100) {
            throw new Error('Paystack amount is invalid.');
        }

        if (!reference) {
            throw new Error('Paystack reference is missing.');
        }

        if (typeof window.PaystackPop === 'undefined') {
            if (authorizationUrl) {
                showSidebarMessage('Opening Paystack secure checkout...', 'success');
                window.location.href = authorizationUrl;
                return;
            }

            throw new Error('Paystack script could not load. Check your internet connection.');
        }

        const callbackFunction = function (response) {
            const paidReference = response && response.reference ? response.reference : reference;

            setButtonLoading(true, 'Verifying Payment...');

            verifyPaystack(paidReference)
                .then(function (verifyResponse) {
                    saveBeneficiary(beneficiaryNumber.value);

                    const toast = verifyResponse.toast || lastToastPayload || {
                        title: 'Order Received',
                        message: `Your ${dataSize?.textContent || 'data'} to ${beneficiaryNumber?.value || ''} has been accepted and is processing.`,
                        delivery: 'Delivery: 30-60 mins',
                        buy_again: cfg().dashboardUrl || window.location.origin
                    };

                    showOrderToast(toast);
                    showSidebarMessage('Payment successful. Your order is being processed.', 'success');
                    redirectToDashboard(1800);
                })
                .catch(function (error) {
                    showSidebarMessage(error.message || 'Payment verification failed.', 'error');
                    setButtonLoading(false);
                });
        };

        const closeFunction = function () {
            setButtonLoading(false);
            showSidebarMessage('Payment window closed. You can try again.', 'error');
        };

        try {
            const handler = window.PaystackPop.setup({
                key: publicKey,
                email: email,
                amount: payAmount,
                currency: initData.currency || 'GHS',
                ref: reference,
                callback: callbackFunction,
                onClose: closeFunction
            });

            handler.openIframe();
        } catch (error) {
            console.error('Paystack popup failed:', error);

            if (authorizationUrl) {
                showSidebarMessage('Opening Paystack secure checkout...', 'success');
                window.location.href = authorizationUrl;
                return;
            }

            throw new Error(error.message || 'Unable to open Paystack checkout.');
        }
    }

    async function startPaystackPayment() {
        setButtonLoading(true, 'Opening Paystack...');

        const initData = await initializePaystack();

        console.log('Paystack initialized:', initData);

        openPaystackCheckout(initData);
    }

    async function payWithWallet() {
        setButtonLoading(true, 'Paying With Wallet...');

        const payload = {
            plan_id: planId ? planId.value : '',
            network_name: networkInput ? networkInput.value : '',
            recipient_phone: beneficiaryNumber ? beneficiaryNumber.value : '',
            is_ported: portedNumber && portedNumber.checked ? 1 : 0
        };

        const response = await postJson(cfg().walletPaymentUrl, payload);

        saveBeneficiary(beneficiaryNumber.value);

        const toast = response.toast || {
            title: 'Order Received',
            message: `Your ${dataSize?.textContent || 'data'} to ${beneficiaryNumber?.value || ''} has been accepted and is processing.`,
            delivery: 'Delivery: 30-60 mins',
            buy_again: cfg().dashboardUrl || window.location.origin
        };

        showOrderToast(toast);
        showSidebarMessage('Wallet payment successful. Your order is being processed.', 'success');

        redirectToDashboard(1800);
    }

    document.querySelectorAll('.kd-open-purchase-sheet').forEach(button => {
        button.addEventListener('click', function () {
            openSheet(this);
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSheet);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSheet);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeSheet();
    });

    paymentMethodOptions.forEach(option => {
        option.addEventListener('click', function () {
            selectPaymentMethod(this.dataset.paymentMethod || 'paystack');
        });
    });

    if (beneficiaryNumber) {
        beneficiaryNumber.addEventListener('focus', renderBeneficiarySuggestions);

        beneficiaryNumber.addEventListener('input', function () {
            this.value = onlyNumbers(this.value);
            clearSidebarMessage();
            clearFieldError(beneficiaryNumber, beneficiaryError);
            updatePortedVisibility(this.value);
            renderBeneficiarySuggestions();

            if (beneficiaryHint) {
                beneficiaryHint.textContent = 'Number to receive the data.';
            }

            if (this.value.length === 10) {
                validateBeneficiary(true);
            }
        });

        beneficiaryNumber.addEventListener('blur', function () {
            hideBeneficiarySuggestions();
            validateBeneficiary(true);
        });
    }

    if (portedNumber) {
        portedNumber.addEventListener('change', function () {
            clearSidebarMessage();
            validateBeneficiary(true);
        });
    }

    if (purchaseForm) {
        purchaseForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            clearSidebarMessage();

            if (!validateBeneficiary(true)) {
                if (beneficiaryNumber) beneficiaryNumber.focus();
                return false;
            }

            const method = paymentMethodInput ? paymentMethodInput.value : 'paystack';

            try {
                if (method === 'wallet') {
                    await payWithWallet();
                    return false;
                }

                await startPaystackPayment();
            } catch (error) {
                console.error(error);
                showSidebarMessage(error.message || 'Something went wrong. Please try again.', 'error');
                setButtonLoading(false);
            }

            return false;
        });
    }

    selectPaymentMethod('paystack');
});