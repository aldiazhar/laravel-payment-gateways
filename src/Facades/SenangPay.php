<?php

namespace Aldiazhar\PaymentGateways\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * SenangPay Payment Gateway Facade
 *
 * @method static \Aldiazhar\PaymentGateways\SenangPay\SenangPayService account(?string $account = null)
 * @method static array inputs(array $payload)
 * @method static string url()
 * @method static array check(string $orderId)
 * @method static void verifyOrFail(array $data)
 * @method static bool verify(array $data)
 * @method static void ensureSuccess(array $data)
 * @method static void validatePayment(array $data)
 * @method static array getConfig()
 * @method static string getCurrentAccount()
 * @method static string getName()
 *
 * @see \Aldiazhar\PaymentGateways\SenangPay\SenangPayService
 */
class SenangPay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'payment.senangpay';
    }
}