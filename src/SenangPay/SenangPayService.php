<?php

namespace Aldiazhar\PaymentGateways\SenangPay;

use Aldiazhar\PaymentGateways\Contracts\PaymentGatewayInterface;
use Aldiazhar\PaymentGateways\Exceptions\InvalidHashException;
use Aldiazhar\PaymentGateways\Exceptions\PaymentFailedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SenangPayService implements PaymentGatewayInterface
{
    /**
     * Current configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Payment gateway URL
     *
     * @var string
     */
    private string $url;

    /**
     * Current account name
     *
     * @var string
     */
    private string $currentAccount = 'default';

    /**
     * Initialize service with account configuration
     *
     * @param array $config Configuration array with keys: merchant_id, secret, sandbox
     */
    public function __construct(array $config = [])
    {
        $this->config = empty($config) ? $this->getDefaultConfig() : $config;
        $this->setUrl();
    }

    /**
     * Get default configuration from config file
     *
     * @return array
     */
    private function getDefaultConfig(): array
    {
        return [
            'merchant_id' => config('payment-gateways.senangpay.merchant_id'),
            'secret' => config('payment-gateways.senangpay.secret'),
            'sandbox' => config('payment-gateways.senangpay.sandbox', true),
        ];
    }

    /**
     * Switch to specific account
     *
     * @param string|null $account Account name from config or null for default
     * @return self
     * @throws \InvalidArgumentException
     */
    public function account(?string $account = null): self
    {
        if (empty($account)) {
            $this->currentAccount = 'default';
            $this->config = $this->getDefaultConfig();
            $this->setUrl();
            return $this;
        }

        $this->currentAccount = $account;
        $accountConfig = $this->getAccountConfig($account);

        if (empty($accountConfig['merchant_id']) || empty($accountConfig['secret'])) {
            throw new \InvalidArgumentException(
                "SenangPay account '{$account}' configuration not found or incomplete. " .
                "Please check your payment-gateways.php config file."
            );
        }

        $this->config = $accountConfig;
        $this->setUrl();

        return $this;
    }

    /**
     * Get account configuration from config
     *
     * @param string $account
     * @return array
     */
    private function getAccountConfig(string $account): array
    {
        return [
            'merchant_id' => config("payment-gateways.senangpay.accounts.{$account}.merchant_id"),
            'secret' => config("payment-gateways.senangpay.accounts.{$account}.secret"),
            'sandbox' => config("payment-gateways.senangpay.accounts.{$account}.sandbox", 
                config('payment-gateways.senangpay.sandbox', true)),
        ];
    }

    /**
     * Set payment URL based on sandbox mode
     *
     * @return void
     */
    private function setUrl(): void
    {
        $merchantId = $this->config['merchant_id'];
        $baseUrl = $this->config['sandbox']
            ? 'https://sandbox.senangpay.my'
            : 'https://app.senangpay.my';

        $this->url = "{$baseUrl}/payment/{$merchantId}";
    }

    /**
     * Generate hash for payment
     *
     * @param array $payload Payment data
     * @return string
     */
    private function hash(array $payload): string
    {
        $secret = $this->config['secret'];

        return hash_hmac(
            'sha256',
            $secret
                . urldecode($payload['description'])
                . urldecode($payload['amount'])
                . urldecode($payload['order_id']),
            $secret
        );
    }

    /**
     * Generate hash for transaction query
     * 
     * According to SenangPay API documentation:
     * Hash = MD5(merchant_id + secret_key + order_id)
     *
     * @param string $orderId
     * @return string
     */
    private function queryHash(string $orderId): string
    {
        $merchantId = $this->config['merchant_id'];
        $secret = $this->config['secret'];

        return md5($merchantId . $secret . $orderId);
    }

    /**
     * Prepare payment inputs
     *
     * @param array $payload Payment data with keys:
     *                       - description: Payment description
     *                       - amount: Payment amount
     *                       - order_id: Order/Invoice ID
     *                       - customer_name: Customer name
     *                       - customer_email: Customer email
     *                       - customer_phone: Customer phone
     * @return array Payment form inputs
     */
    public function inputs(array $payload): array
    {
        return [
            'detail' => $payload['description'],
            'amount' => $payload['amount'],
            'order_id' => $payload['order_id'],
            'name' => $payload['customer_name'],
            'email' => $payload['customer_email'],
            'phone' => $payload['customer_phone'],
            'hash' => $this->hash($payload),
        ];
    }

    /**
     * Get payment URL
     *
     * @return string
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * Query transaction status from SenangPay API
     *
     * @param string $orderId Order/Invoice reference number
     * @return array Response with keys:
     *               - status: 1 for paid, 0 for pending/failed
     *               - message: Status message
     *               - data: Transaction data (if available)
     */
    public function check(string $orderId): array
    {
        $hash = $this->queryHash($orderId);
        $baseUrl = $this->config['sandbox']
            ? 'https://sandbox.senangpay.my/apiv1/query_order_status'
            : 'https://app.senangpay.my/apiv1/query_order_status';

        try {
            $response = Http::get($baseUrl, [
                'merchant_id' => $this->config['merchant_id'],
                'order_id' => $orderId,
                'hash' => $hash,
            ]);

            if (!$response->ok()) {
                return [
                    'status' => 0,
                    'message' => 'API request failed',
                    'data' => null,
                ];
            }

            $data = $response->json();

            if ((int) ($data['status'] ?? 0) > 0) {
                $isPaid = false;
                $details = [];

                foreach ($data['data'] as $item) {
                    $status = strtolower($item['payment_info']['status'] ?? '');

                    if ($status === 'paid') {
                        $isPaid = true;
                        $details = $item;
                        break;
                    }
                }

                if ($isPaid) {
                    return [
                        'status' => 1,
                        'message' => 'Payment successful',
                        'data' => $details,
                    ];
                }

                return [
                    'status' => 0,
                    'message' => 'Payment pending',
                    'data' => $data,
                ];
            }

            return [
                'status' => 0,
                'message' => $data['msg'] ?? 'No transaction found',
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('SenangPay check error', [
                'gateway' => 'SenangPay',
                'account' => $this->currentAccount,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 0,
                'message' => 'Transaction query failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Verify callback/return hash and throw exception if invalid
     *
     * @param array $data Callback data with keys: hash, status_id, order_id, transaction_id, msg
     * @return void
     * @throws InvalidHashException
     */
    public function verifyOrFail(array $data): void
    {
        $received = $data['hash'] ?? '';
        $secret = $this->config['secret'];

        $expected = hash_hmac(
            'sha256',
            $secret
                . ($data['status_id'] ?? '')
                . ($data['order_id'] ?? '')
                . ($data['transaction_id'] ?? '')
                . ($data['msg'] ?? ''),
            $secret
        );

        if (!hash_equals($expected, $received)) {
            Log::warning('SenangPay: Invalid hash detected', [
                'gateway' => 'SenangPay',
                'account' => $this->currentAccount,
                'order_id' => $data['order_id'] ?? null,
                'expected_hash' => $expected,
                'received_hash' => $received,
            ]);

            throw new InvalidHashException(
                'Payment verification failed. Invalid hash signature.'
            );
        }
    }

    /**
     * Verify callback hash (returns boolean)
     *
     * @param array $data Callback data
     * @return bool
     */
    public function verify(array $data): bool
    {
        try {
            $this->verifyOrFail($data);
            return true;
        } catch (InvalidHashException $e) {
            return false;
        }
    }

    /**
     * Check if payment is successful and throw exception if not
     *
     * @param array $data Callback/return data with keys: status_id, msg, order_id
     * @return void
     * @throws PaymentFailedException
     */
    public function ensureSuccess(array $data): void
    {
        $statusId = (int) ($data['status_id'] ?? 0);
        
        if ($statusId !== 1) {
            $message = $data['msg'] ?? 'Payment failed';
            
            Log::info('SenangPay: Payment failed', [
                'gateway' => 'SenangPay',
                'account' => $this->currentAccount,
                'order_id' => $data['order_id'] ?? null,
                'status_id' => $statusId,
                'message' => $message,
            ]);

            throw new PaymentFailedException(
                'Payment was not successful: ' . str_replace('_', ' ', $message)
            );
        }
    }

    /**
     * Verify and ensure payment success in one call
     *
     * @param array $data Callback/return data
     * @return void
     * @throws InvalidHashException|PaymentFailedException
     */
    public function validatePayment(array $data): void
    {
        $this->verifyOrFail($data);
        $this->ensureSuccess($data);
    }

    /**
     * Get current config
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get current account name
     *
     * @return string
     */
    public function getCurrentAccount(): string
    {
        return $this->currentAccount;
    }

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'SenangPay';
    }
}