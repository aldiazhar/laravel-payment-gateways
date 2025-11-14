<?php

namespace Aldi\PaymentGateways\Tests\Unit;

use Aldi\PaymentGateways\Exceptions\InvalidHashException;
use Aldi\PaymentGateways\Exceptions\PaymentFailedException;
use Aldi\PaymentGateways\SenangPay\SenangPayService;
use Aldi\PaymentGateways\Tests\TestCase;

class SenangPayServiceTest extends TestCase
{
    protected SenangPayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SenangPayService();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SenangPayService::class, $this->service);
    }

    /** @test */
    public function it_returns_correct_gateway_name()
    {
        $this->assertEquals('SenangPay', $this->service->getName());
    }

    /** @test */
    public function it_returns_default_configuration()
    {
        $config = $this->service->getConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('merchant_id', $config);
        $this->assertArrayHasKey('secret', $config);
        $this->assertArrayHasKey('sandbox', $config);
        $this->assertEquals('test_merchant', $config['merchant_id']);
    }

    /** @test */
    public function it_returns_correct_payment_url_for_sandbox()
    {
        $url = $this->service->url();
        
        $this->assertStringContainsString('sandbox.senangpay.my', $url);
        $this->assertStringContainsString('test_merchant', $url);
    }

    /** @test */
    public function it_can_switch_to_different_account()
    {
        $this->service->account('secondary');
        
        $config = $this->service->getConfig();
        
        $this->assertEquals('test_merchant_2', $config['merchant_id']);
        $this->assertEquals('secondary', $this->service->getCurrentAccount());
    }

    /** @test */
    public function it_can_switch_back_to_default_account()
    {
        $this->service->account('secondary');
        $this->service->account();
        
        $config = $this->service->getConfig();
        
        $this->assertEquals('test_merchant', $config['merchant_id']);
        $this->assertEquals('default', $this->service->getCurrentAccount());
    }

    /** @test */
    public function it_throws_exception_for_invalid_account()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("SenangPay account 'invalid' configuration not found");
        
        $this->service->account('invalid');
    }

    /** @test */
    public function it_generates_payment_inputs()
    {
        $payload = [
            'description' => 'Test Payment',
            'amount' => '50.00',
            'order_id' => 'INV-001',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '60123456789',
        ];

        $inputs = $this->service->inputs($payload);

        $this->assertIsArray($inputs);
        $this->assertArrayHasKey('detail', $inputs);
        $this->assertArrayHasKey('amount', $inputs);
        $this->assertArrayHasKey('order_id', $inputs);
        $this->assertArrayHasKey('name', $inputs);
        $this->assertArrayHasKey('email', $inputs);
        $this->assertArrayHasKey('phone', $inputs);
        $this->assertArrayHasKey('hash', $inputs);

        $this->assertEquals('Test Payment', $inputs['detail']);
        $this->assertEquals('50.00', $inputs['amount']);
        $this->assertEquals('INV-001', $inputs['order_id']);
        $this->assertEquals('John Doe', $inputs['name']);
    }

    /** @test */
    public function it_generates_valid_hash_for_payment()
    {
        $payload = [
            'description' => 'Test Payment',
            'amount' => '50.00',
            'order_id' => 'INV-001',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '60123456789',
        ];

        $inputs = $this->service->inputs($payload);

        // Verify hash is 64 characters (SHA-256)
        $this->assertEquals(64, strlen($inputs['hash']));
        
        // Verify hash format
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $inputs['hash']);
    }

    /** @test */
    public function it_verifies_valid_callback_data()
    {
        $callbackData = $this->generateValidCallbackData();

        $result = $this->service->verify($callbackData);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_callback_hash()
    {
        $callbackData = $this->generateValidCallbackData();
        $callbackData['hash'] = 'invalid_hash';

        $result = $this->service->verify($callbackData);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_hash_when_using_verify_or_fail()
    {
        $this->expectException(InvalidHashException::class);
        $this->expectExceptionMessage('Invalid hash signature');

        $callbackData = $this->generateValidCallbackData();
        $callbackData['hash'] = 'invalid_hash';

        $this->service->verifyOrFail($callbackData);
    }

    /** @test */
    public function it_does_not_throw_exception_for_valid_hash()
    {
        $callbackData = $this->generateValidCallbackData();

        // Should not throw exception
        $this->service->verifyOrFail($callbackData);

        $this->assertTrue(true); // If we get here, test passed
    }

    /** @test */
    public function it_throws_exception_for_failed_payment()
    {
        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('Payment was not successful');

        $callbackData = $this->generateValidCallbackData();
        $callbackData['status_id'] = '0'; // Failed payment

        // Regenerate hash for new status
        $callbackData['hash'] = $this->generateHash($callbackData);

        $this->service->ensureSuccess($callbackData);
    }

    /** @test */
    public function it_does_not_throw_exception_for_successful_payment()
    {
        $callbackData = $this->generateValidCallbackData();

        // Should not throw exception
        $this->service->ensureSuccess($callbackData);

        $this->assertTrue(true); // If we get here, test passed
    }

    /** @test */
    public function it_validates_payment_successfully()
    {
        $callbackData = $this->generateValidCallbackData();

        // Should not throw any exception
        $this->service->validatePayment($callbackData);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_invalid_hash_exception_during_validation()
    {
        $this->expectException(InvalidHashException::class);

        $callbackData = $this->generateValidCallbackData();
        $callbackData['hash'] = 'invalid_hash';

        $this->service->validatePayment($callbackData);
    }

    /** @test */
    public function it_throws_payment_failed_exception_during_validation()
    {
        $this->expectException(PaymentFailedException::class);

        $callbackData = $this->generateValidCallbackData();
        $callbackData['status_id'] = '0';
        
        // Regenerate valid hash for failed payment
        $callbackData['hash'] = $this->generateHash($callbackData);

        $this->service->validatePayment($callbackData);
    }

    /**
     * Generate valid callback data for testing
     *
     * @return array
     */
    protected function generateValidCallbackData(): array
    {
        $data = [
            'status_id' => '1',
            'order_id' => 'INV-001',
            'transaction_id' => 'TXN-123456',
            'msg' => 'Payment_was_successful',
        ];

        $data['hash'] = $this->generateHash($data);

        return $data;
    }

    /**
     * Generate hash for callback data
     *
     * @param array $data
     * @return string
     */
    protected function generateHash(array $data): string
    {
        $secret = 'test_secret_key';

        return hash_hmac(
            'sha256',
            $secret
                . $data['status_id']
                . $data['order_id']
                . $data['transaction_id']
                . $data['msg'],
            $secret
        );
    }
}