<?php

namespace App\Support;

class PaymentGatewayCatalog
{
    public const PROVIDERS = [
        'braintree' => [
            'label' => 'Braintree',
            'fields' => [
                'payment_gateway_name' => ['label' => 'Payment Gateway Name', 'required' => true],
                'merchant_id' => ['label' => 'Merchant ID', 'required' => true],
                'public_key' => ['label' => 'Public Key', 'required' => true, 'secret' => true],
                'private_key' => ['label' => 'Private Key', 'required' => true, 'secret' => true],
                'use_sandbox' => ['label' => 'Use Sandbox', 'type' => 'boolean'],
            ],
        ],
        'gocardless' => [
            'label' => 'GoCardless',
            'fields' => [
                'payment_gateway_name' => ['label' => 'Payment Gateway Name', 'required' => true],
                'access_token' => ['label' => 'Access Token', 'required' => true, 'secret' => true],
                'webhooks_secret' => ['label' => 'Webhooks Secret', 'required' => true, 'secret' => true],
                'use_sandbox' => ['label' => 'Use Sandbox', 'type' => 'boolean'],
            ],
        ],
        'mpesa' => [
            'label' => 'Mpesa',
            'fields' => [
                'payment_gateway_name' => ['label' => 'Payment Gateway Name', 'required' => true],
                'consumer_key' => ['label' => 'Consumer Key', 'required' => true, 'secret' => true],
                'consumer_secret' => ['label' => 'Consumer Secret', 'required' => true, 'secret' => true],
                'initiator_name' => ['label' => 'Initiator Name'],
                'till_number' => ['label' => 'Till Number', 'required' => true],
                'transaction_limit' => ['label' => 'Transaction Limit', 'type' => 'number'],
                'business_shortcode' => ['label' => 'Business Shortcode'],
                'online_passkey' => ['label' => 'Online PassKey', 'required' => true, 'secret' => true],
                'security_credential' => ['label' => 'Security Credential', 'type' => 'textarea', 'secret' => true],
                'sandbox' => ['label' => 'Sandbox', 'type' => 'boolean'],
            ],
        ],
        'paymob' => [
            'label' => 'Paymob',
            'fields' => [
                'api_key' => ['label' => 'API Key', 'required' => true, 'secret' => true],
                'public_key' => ['label' => 'Public Key', 'required' => true],
                'hmac' => ['label' => 'HMAC', 'required' => true, 'secret' => true],
                'secret_key' => ['label' => 'Secret Key', 'required' => true, 'secret' => true],
                'iframe' => ['label' => 'Iframe', 'required' => true],
                'payment_integration' => ['label' => 'Payment Integration', 'required' => true, 'type' => 'number'],
                'redirect_to' => ['label' => 'Redirect To'],
            ],
        ],
        'paypal' => [
            'label' => 'PayPal',
            'fields' => [
                'api_username' => ['label' => 'API Username', 'required' => true],
                'api_password' => ['label' => 'API Password', 'required' => true, 'secret' => true],
                'signature' => ['label' => 'Signature', 'required' => true, 'secret' => true],
                'redirect_to' => ['label' => 'Redirect To'],
                'use_sandbox' => ['label' => 'Use Sandbox', 'type' => 'boolean'],
                'client_id' => ['label' => 'REST Client ID', 'required' => true],
                'client_secret' => ['label' => 'REST Client Secret', 'required' => true, 'secret' => true],
                'webhook_id' => ['label' => 'Webhook ID', 'required' => true],
            ],
        ],
        'paytm' => [
            'label' => 'Paytm',
            'fields' => [
                'merchant_id' => ['label' => 'Merchant ID', 'required' => true],
                'merchant_key' => ['label' => 'Merchant Key', 'required' => true, 'secret' => true],
                'industry_type_id' => ['label' => 'Industry Type ID'],
                'website' => ['label' => 'Website'],
                'staging' => ['label' => 'Staging', 'type' => 'boolean'],
            ],
        ],
        'razorpay' => ['label' => 'Razorpay', 'fields' => []],
        'stripe' => [
            'label' => 'Stripe',
            'fields' => [
                'payment_gateway_name' => ['label' => 'Payment Gateway Name', 'required' => true],
                'publishable_key' => ['label' => 'Publishable Key', 'required' => true],
                'secret_key' => ['label' => 'Secret Key', 'required' => true, 'secret' => true],
                'webhook_secret' => ['label' => 'Webhook Signing Secret', 'required' => true, 'secret' => true],
                'redirect_url' => ['label' => 'Redirect URL'],
            ],
        ],
    ];
}
