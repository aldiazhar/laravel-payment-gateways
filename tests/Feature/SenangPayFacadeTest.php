<?php

namespace Aldiazhar\PaymentGateways\Tests\Feature;

use Aldiazhar\PaymentGateways\Exceptions\InvalidHashException;
use Aldiazhar\PaymentGateways\Exceptions\PaymentFailedException;
use Aldiazhar\PaymentGateways\Facades\SenangPay;
use Aldiazhar\PaymentGateways\Tests\TestCase;

class SenangPayFacadeTest extends TestCase
{
    /** @test */
    public function facade_can_be_resolved()
    {
        $this->assertNotNull(SenangPay::getFacadeRoot());
    }

    /** @test */
    public function facade_returns_gateway_name()
    {
        $this->assertEquals('SenangPay', SenangPay::getName());
    }

    /** @test */
    public function facade_can_generate_payment_inputs()
    {
        $payload = [
            'description' => 'Facade Test Payment',
            'amount' => '100.00',
            'order_id' => 'INV-FACADE-001',
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '60129876543',
        ];

        $inputs = SenangPay::inputs($payload);

        $this->assertIsArray($inputs);
        $this->assertArrayHasKey('hash', $inputs);
        $this->assertEquals('Facade Test Payment', $inputs['detail']);
    }

    /** @test */
    public function facade_can_get_payment_url()
    {
        $url = SenangPay::url();

        $this->assertIsString($url);
        $this->assertStringContainsString('senangpay.my', $url);
    }

    /** @test */
    public function facade_can_switch_accounts()
    {
        SenangPay::account('secondary');
        
        $config = SenangPay::getConfig();
        
        $this->assertEquals('test_merchant_2', $config['merchant_id']);
        $this->assertEquals('secondary', SenangPay::getCurrentAccount());

        // Reset to default
        SenangPay::account();
    }

    /** @test */
    public function facade_can_verify_valid_payment()
    {
        $data = $this->generateValidCallbackData();

        $result = SenangPay::verify($data);

        $this->assertTrue($result);
    }

    /** @test */
    public function facade_rejects_invalid_payment()
    {
        $data = $this->generateValidCallbackData();
        $data['hash'] = 'tampered_hash';

        $result = SenangPay::verify($data);

        $this->assertFalse($result);
    }

    /** @test */
    public function facade_verify_or_fail_throws_exception_for_invalid_hash()
    {
        $this->expectException(InvalidHashException::class);

        $data = $this->generateValidCallbackData();
        $data['hash'] = 'invalid_hash';

        SenangPay::verifyOrFail($data);
    }

    /** @test */
    public function facade_ensure_success_throws_exception_for_failed_payment()
    {
        $this->expectException(PaymentFailedException::class);

        $data = $this->generateValidCallbackData();
        $data['status_id'] = '0';
        $data['hash'] = $this->generateHash($data);

        SenangPay::ensureSuccess($data);
    }

    /** @test */
    public function facade_validate_payment_works_for_valid_payment()
    {
        $data = $this->generateValidCallbackData();

        // Should not throw exception
        SenangPay::validatePayment($data);

        $this->assertTrue(true);
    }

    /** @test */
    public function facade_validate_payment_throws_exception_for_invalid_payment()
    {
        $this->expectException(InvalidHashException::class);

        $data = $this->generateValidCallbackData();
        $data['hash'] = 'wrong_hash';

        SenangPay::validatePayment($data);
    }

    /**
     * Generate valid callback data
     *
     * @return array
     */
    protected function generateValidCallbackData(): array
    {
        $data = [
            'status_id' => '1',
            'order_id' => 'INV-FACADE-001',
            'transaction_id' => 'TXN-FACADE-123',
            'msg' => 'Payment_successful',
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