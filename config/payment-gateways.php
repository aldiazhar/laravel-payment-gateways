<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SenangPay Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Configuration for SenangPay payment gateway integration.
    | Get your credentials from: https://app.senangpay.my/
    |
    */

    'senangpay' => [
        'merchant_id' => env('SENANGPAY_MERCHANT_ID'),
        'secret' => env('SENANGPAY_SECRET_KEY'),
        'sandbox' => env('SENANGPAY_SANDBOX', true),

        /*
        |--------------------------------------------------------------------------
        | Multiple Accounts
        |--------------------------------------------------------------------------
        |
        | You can configure multiple SenangPay merchant accounts here.
        | Use the account() method to switch between accounts.
        |
        | Example: SenangPay::account('secondary')->inputs($payload)
        |
        */

        'accounts' => [
            'secondary' => [
                'merchant_id' => env('SENANGPAY_SECONDARY_MERCHANT_ID'),
                'secret' => env('SENANGPAY_SECONDARY_SECRET_KEY'),
                'sandbox' => env('SENANGPAY_SECONDARY_SANDBOX', true),
            ],

            // Add more accounts as needed
            // 'tertiary' => [
            //     'merchant_id' => env('SENANGPAY_TERTIARY_MERCHANT_ID'),
            //     'secret' => env('SENANGPAY_TERTIARY_SECRET_KEY'),
            //     'sandbox' => env('SENANGPAY_TERTIARY_SANDBOX', true),
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | iPay88 Payment Gateway (Coming Soon)
    |--------------------------------------------------------------------------
    |
    | Configuration for iPay88 payment gateway integration.
    | Get your credentials from: https://www.ipay88.com.my/
    |
    */

    'ipay88' => [
        'merchant_code' => env('IPAY88_MERCHANT_CODE'),
        'merchant_key' => env('IPAY88_MERCHANT_KEY'),
        'sandbox' => env('IPAY88_SANDBOX', true),

        'accounts' => [
            // 'secondary' => [
            //     'merchant_code' => env('IPAY88_SECONDARY_MERCHANT_CODE'),
            //     'merchant_key' => env('IPAY88_SECONDARY_MERCHANT_KEY'),
            //     'sandbox' => env('IPAY88_SECONDARY_SANDBOX', true),
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal Payment Gateway (Coming Soon)
    |--------------------------------------------------------------------------
    |
    | Configuration for PayPal payment gateway integration.
    | Get your credentials from: https://developer.paypal.com/
    |
    */

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'sandbox' => env('PAYPAL_SANDBOX', true),

        'accounts' => [
            // 'secondary' => [
            //     'client_id' => env('PAYPAL_SECONDARY_CLIENT_ID'),
            //     'secret' => env('PAYPAL_SECONDARY_SECRET'),
            //     'sandbox' => env('PAYPAL_SECONDARY_SANDBOX', true),
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Billplz Payment Gateway (Coming Soon)
    |--------------------------------------------------------------------------
    |
    | Configuration for Billplz payment gateway integration.
    | Get your credentials from: https://www.billplz.com/
    |
    */

    'billplz' => [
        'api_key' => env('BILLPLZ_API_KEY'),
        'collection_id' => env('BILLPLZ_COLLECTION_ID'),
        'x_signature' => env('BILLPLZ_X_SIGNATURE'),
        'sandbox' => env('BILLPLZ_SANDBOX', true),

        'accounts' => [
            // 'secondary' => [
            //     'api_key' => env('BILLPLZ_SECONDARY_API_KEY'),
            //     'collection_id' => env('BILLPLZ_SECONDARY_COLLECTION_ID'),
            //     'x_signature' => env('BILLPLZ_SECONDARY_X_SIGNATURE'),
            //     'sandbox' => env('BILLPLZ_SECONDARY_SANDBOX', true),
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Midtrans Payment Gateway (Coming Soon)
    |--------------------------------------------------------------------------
    |
    | Configuration for Midtrans payment gateway integration.
    | Get your credentials from: https://dashboard.midtrans.com/
    |
    */

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'sandbox' => env('MIDTRANS_SANDBOX', true),

        'accounts' => [
            // 'secondary' => [
            //     'server_key' => env('MIDTRANS_SECONDARY_SERVER_KEY'),
            //     'client_key' => env('MIDTRANS_SECONDARY_CLIENT_KEY'),
            //     'merchant_id' => env('MIDTRANS_SECONDARY_MERCHANT_ID'),
            //     'sandbox' => env('MIDTRANS_SECONDARY_SANDBOX', true),
            // ],
        ],
    ],

];