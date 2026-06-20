<?php

return [
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'payment_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    'currency' => env('PAYSTACK_CURRENCY', 'GHS'),
    'webhook_url' => env('PAYSTACK_WEBHOOK_URL'),
];