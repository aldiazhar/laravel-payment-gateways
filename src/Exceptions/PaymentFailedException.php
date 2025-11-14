<?php

namespace Aldi\PaymentGateways\Exceptions;

/**
 * Exception thrown when payment is not successful
 */
class PaymentFailedException extends \Exception
{
    /**
     * Create a new PaymentFailedException instance
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Payment failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}