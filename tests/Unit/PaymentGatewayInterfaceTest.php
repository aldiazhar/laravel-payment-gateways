<?php

namespace Aldiazhar\PaymentGateways\Tests\Unit;

use Aldiazhar\PaymentGateways\Contracts\PaymentGatewayInterface;
use Aldiazhar\PaymentGateways\SenangPay\SenangPayService;
use Aldiazhar\PaymentGateways\Tests\TestCase;

class PaymentGatewayInterfaceTest extends TestCase
{
    /** @test */
    public function senangpay_service_implements_payment_gateway_interface()
    {
        $service = new SenangPayService();

        $this->assertInstanceOf(PaymentGatewayInterface::class, $service);
    }

    /** @test */
    public function interface_defines_all_required_methods()
    {
        $interface = new \ReflectionClass(PaymentGatewayInterface::class);
        $methods = $interface->getMethods();

        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $requiredMethods = [
            'inputs',
            'url',
            'check',
            'verify',
            'verifyOrFail',
            'ensureSuccess',
            'validatePayment',
            'account',
            'getName',
            'getConfig',
            'getCurrentAccount',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertContains($method, $methodNames, "Method {$method} not found in interface");
        }
    }

    /** @test */
    public function senangpay_implements_all_interface_methods()
    {
        $service = new SenangPayService();
        
        $this->assertTrue(method_exists($service, 'inputs'));
        $this->assertTrue(method_exists($service, 'url'));
        $this->assertTrue(method_exists($service, 'check'));
        $this->assertTrue(method_exists($service, 'verify'));
        $this->assertTrue(method_exists($service, 'verifyOrFail'));
        $this->assertTrue(method_exists($service, 'ensureSuccess'));
        $this->assertTrue(method_exists($service, 'validatePayment'));
        $this->assertTrue(method_exists($service, 'account'));
        $this->assertTrue(method_exists($service, 'getName'));
        $this->assertTrue(method_exists($service, 'getConfig'));
        $this->assertTrue(method_exists($service, 'getCurrentAccount'));
    }

    /** @test */
    public function interface_methods_have_correct_return_types()
    {
        $service = new SenangPayService();

        // Test inputs() returns array
        $this->assertIsArray($service->inputs($this->getTestPayload()));

        // Test url() returns string
        $this->assertIsString($service->url());

        // Test getName() returns string
        $this->assertIsString($service->getName());

        // Test getConfig() returns array
        $this->assertIsArray($service->getConfig());

        // Test getCurrentAccount() returns string
        $this->assertIsString($service->getCurrentAccount());

        // Test account() returns self
        $this->assertInstanceOf(SenangPayService::class, $service->account());

        // Test verify() returns bool
        $this->assertIsBool($service->verify($this->getValidCallbackData()));
    }

    /**
     * Get test payment payload
     */
    protected function getTestPayload(): array
    {
        return [
            'description' => 'Test',
            'amount' => '10.00',
            'order_id' => 'TEST-001',
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'customer_phone' => '60123456789',
        ];
    }

    /**
     * Get valid callback data
     */
    protected function getValidCallbackData(): array
    {
        $data = [
            'status_id' => '1',
            'order_id' => 'TEST-001',
            'transaction_id' => 'TXN-001',
            'msg' => 'Success',
        ];

        $secret = 'test_secret_key';
        $data['hash'] = hash_hmac(
            'sha256',
            $secret . $data['status_id'] . $data['order_id'] . $data['transaction_id'] . $data['msg'],
            $secret
        );

        return $data;
    }
}