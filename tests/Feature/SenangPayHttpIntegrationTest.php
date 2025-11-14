<?php

namespace Aldiazhar\PaymentGateways\Tests\Feature;

use Aldiazhar\PaymentGateways\Facades\SenangPay;
use Aldiazhar\PaymentGateways\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class SenangPayHttpIntegrationTest extends TestCase
{
    /** @test */
    public function it_can_check_transaction_status_with_mocked_response()
    {
        // Mock successful payment response
        Http::fake([
            'sandbox.senangpay.my/apiv1/query_order_status*' => Http::response([
                'status' => 1,
                'msg' => 'Success',
                'data' => [
                    [
                        'payment_info' => [
                            'status' => 'paid',
                            'transaction_id' => 'TXN-123456',
                            'amount' => '50.00',
                        ],
                        'order_id' => 'INV-001',
                    ],
                ],
            ], 200),
        ]);

        $result = SenangPay::check('INV-001');

        $this->assertEquals(1, $result['status']);
        $this->assertEquals('Payment successful', $result['message']);
        $this->assertIsArray($result['data']);
        $this->assertEquals('paid', $result['data']['payment_info']['status']);
    }

    /** @test */
    public function it_handles_pending_payment_status()
    {
        // Mock pending payment response
        Http::fake([
            'sandbox.senangpay.my/apiv1/query_order_status*' => Http::response([
                'status' => 1,
                'msg' => 'Success',
                'data' => [
                    [
                        'payment_info' => [
                            'status' => 'pending',
                            'transaction_id' => 'TXN-123456',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = SenangPay::check('INV-002');

        $this->assertEquals(0, $result['status']);
        $this->assertEquals('Payment pending', $result['message']);
    }

    /** @test */
    public function it_handles_no_transaction_found()
    {
        // Mock no transaction found
        Http::fake([
            'sandbox.senangpay.my/apiv1/query_order_status*' => Http::response([
                'status' => 0,
                'msg' => 'No transaction found',
            ], 200),
        ]);

        $result = SenangPay::check('INV-NOTFOUND');

        $this->assertEquals(0, $result['status']);
        $this->assertEquals('No transaction found', $result['message']);
    }

    /** @test */
    public function it_handles_api_request_failure()
    {
        // Mock API failure
        Http::fake([
            'sandbox.senangpay.my/apiv1/query_order_status*' => Http::response(null, 500),
        ]);

        $result = SenangPay::check('INV-ERROR');

        $this->assertEquals(0, $result['status']);
        $this->assertEquals('API request failed', $result['message']);
    }

    /** @test */
    public function it_sends_correct_parameters_to_api()
    {
        Http::fake([
            'sandbox.senangpay.my/*' => Http::response([
                'status' => 0,
                'msg' => 'No transaction found',
            ], 200),
        ]);

        SenangPay::check('INV-PARAMS-TEST');

        // Assert that HTTP request was made with correct parameters
        Http::assertSent(function ($request) {
            // Check if it's a GET request to the query endpoint
            $isCorrectUrl = str_contains($request->url(), 'sandbox.senangpay.my/apiv1/query_order_status');
            
            // Get query parameters
            $query = $request->data();
            
            // Verify all required parameters are present
            $hasMerchantId = isset($query['merchant_id']) && $query['merchant_id'] === 'test_merchant';
            $hasOrderId = isset($query['order_id']) && $query['order_id'] === 'INV-PARAMS-TEST';
            $hasHash = isset($query['hash']) && !empty($query['hash']);
            
            // Verify hash is MD5 (32 characters)
            $isValidHash = isset($query['hash']) && strlen($query['hash']) === 32;
            
            return $isCorrectUrl && $hasMerchantId && $hasOrderId && $hasHash && $isValidHash;
        });
    }

    /** @test */
    public function it_uses_production_url_when_sandbox_is_disabled()
    {
        // Change config to production mode
        config(['payment-gateways.senangpay.sandbox' => false]);

        Http::fake([
            'app.senangpay.my/apiv1/query_order_status*' => Http::response([
                'status' => 0,
                'msg' => 'No transaction found',
            ], 200),
        ]);

        // Create new service instance with updated config
        $service = new \Aldiazhar\PaymentGateways\SenangPay\SenangPayService();
        $service->check('INV-PROD-TEST');

        // Assert production URL was used
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'app.senangpay.my');
        });

        // Reset to sandbox
        config(['payment-gateways.senangpay.sandbox' => true]);
    }

    /** @test */
    public function it_handles_network_exception()
    {
        // Mock network exception
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $result = SenangPay::check('INV-NETWORK-ERROR');

        $this->assertEquals(0, $result['status']);
        $this->assertStringContainsString('Transaction query failed', $result['message']);
    }
}