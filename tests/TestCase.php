<?php

namespace Aldi\PaymentGateways\Tests;

use Aldi\PaymentGateways\PaymentGatewaysServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup if needed
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            PaymentGatewaysServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app): array
    {
        return [
            'SenangPay' => \Aldi\PaymentGateways\Facades\SenangPay::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup payment gateway config
        $app['config']->set('payment-gateways.senangpay', [
            'merchant_id' => 'test_merchant',
            'secret' => 'test_secret_key',
            'sandbox' => true,
            'accounts' => [
                'secondary' => [
                    'merchant_id' => 'test_merchant_2',
                    'secret' => 'test_secret_key_2',
                    'sandbox' => true,
                ],
            ],
        ]);
    }
}