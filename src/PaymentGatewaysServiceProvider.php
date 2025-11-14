<?php

namespace Aldi\PaymentGateways;

use Aldi\PaymentGateways\SenangPay\SenangPayService;
use Illuminate\Support\ServiceProvider;

class PaymentGatewaysServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/payment-gateways.php',
            'payment-gateways'
        );

        // Register SenangPay service
        $this->app->singleton('payment.senangpay', function ($app) {
            return new SenangPayService();
        });

        // TODO: Register other gateways when implemented
        
        // iPay88
        // $this->app->singleton('payment.ipay88', function ($app) {
        //     return new \Aldi\PaymentGateways\IPay88\IPay88Service();
        // });
        
        // PayPal
        // $this->app->singleton('payment.paypal', function ($app) {
        //     return new \Aldi\PaymentGateways\PayPal\PayPalService();
        // });
        
        // Billplz
        // $this->app->singleton('payment.billplz', function ($app) {
        //     return new \Aldi\PaymentGateways\Billplz\BillplzService();
        // });
        
        // Midtrans
        // $this->app->singleton('payment.midtrans', function ($app) {
        //     return new \Aldi\PaymentGateways\Midtrans\MidtransService();
        // });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/payment-gateways.php' => config_path('payment-gateways.php'),
            ], 'payment-gateways-config');
        }
    }
}