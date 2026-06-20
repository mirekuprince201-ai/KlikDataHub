document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Auto Uppercase Names
    |--------------------------------------------------------------------------
    */

    const nameFields = document.querySelectorAll(
        '#first_name, #last_name, #other_name'
    );

    nameFields.forEach(field => {
        field.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Phone Number Validation
    |--------------------------------------------------------------------------
    */

    const phoneField = document.getElementById('phone');

    if (phoneField) {
        phoneField.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');

            if (this.value.length > 10) {
                this.value = this.value.substring(0, 10);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Password Show / Hide
    |--------------------------------------------------------------------------
    */

    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = 'Hide';
            } else {
                input.type = 'password';
                this.textContent = 'Show';
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Password Match Check
    |--------------------------------------------------------------------------
    */

    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');

    function validatePasswords() {
        if (!password || !confirmPassword) return true;

        if (
            confirmPassword.value !== '' &&
            password.value !== confirmPassword.value
        ) {
            confirmPassword.style.borderColor = '#dc2626';
            return false;
        }

        confirmPassword.style.borderColor = '';
        return true;
    }

    if (password && confirmPassword) {
        password.addEventListener('keyup', validatePasswords);
        confirmPassword.addEventListener('keyup', validatePasswords);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Register Form Submit Loader
    |--------------------------------------------------------------------------
    */

    const registerForm = document.getElementById('adminRegisterForm');
    const registerBtn = document.getElementById('registerBtn');

    if (registerForm && registerBtn) {
        registerForm.addEventListener('submit', function (event) {
            const btnText = registerBtn.querySelector('.btn-text');
            const btnLoader = registerBtn.querySelector('.btn-loader');

            if (!validatePasswords()) {
                event.preventDefault();
                alert('Passwords do not match.');
                return;
            }

            registerBtn.disabled = true;

            if (btnText) {
                btnText.style.display = 'none';
            }

            if (btnLoader) {
                btnLoader.style.display = 'inline-block';
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Login Form Submit Loader
    |--------------------------------------------------------------------------
    */

    const loginForm = document.getElementById('adminLoginForm');
    const loginBtn = document.getElementById('loginBtn');

    if (loginForm && loginBtn) {
        loginForm.addEventListener('submit', function () {
            const btnText = loginBtn.querySelector('.btn-text');
            const btnLoader = loginBtn.querySelector('.btn-loader');

            loginBtn.disabled = true;

            if (btnText) {
                btnText.style.display = 'none';
            }

            if (btnLoader) {
                btnLoader.style.display = 'inline-block';
            }
        });
    }

});