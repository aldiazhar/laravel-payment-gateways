<?php

namespace Aldi\PaymentGateways\Exceptions;

/**
 * Exception thrown when payment hash/signature verification fails
 */
class InvalidHashException extends \Exception
{
    /**
     * Create a new InvalidHashException instance
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Invalid hash signature',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}