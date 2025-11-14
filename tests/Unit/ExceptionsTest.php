<?php

namespace Aldiazhar\PaymentGateways\Tests\Unit;

use Aldiazhar\PaymentGateways\Exceptions\InvalidHashException;
use Aldiazhar\PaymentGateways\Exceptions\PaymentFailedException;
use Aldiazhar\PaymentGateways\Tests\TestCase;

class ExceptionsTest extends TestCase
{
    /** @test */
    public function invalid_hash_exception_can_be_thrown()
    {
        $this->expectException(InvalidHashException::class);
        $this->expectExceptionMessage('Invalid hash signature');

        throw new InvalidHashException();
    }

    /** @test */
    public function invalid_hash_exception_can_have_custom_message()
    {
        $this->expectException(InvalidHashException::class);
        $this->expectExceptionMessage('Custom error message');

        throw new InvalidHashException('Custom error message');
    }

    /** @test */
    public function invalid_hash_exception_extends_exception()
    {
        $exception = new InvalidHashException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /** @test */
    public function payment_failed_exception_can_be_thrown()
    {
        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('Payment failed');

        throw new PaymentFailedException();
    }

    /** @test */
    public function payment_failed_exception_can_have_custom_message()
    {
        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('Payment was declined by bank');

        throw new PaymentFailedException('Payment was declined by bank');
    }

    /** @test */
    public function payment_failed_exception_extends_exception()
    {
        $exception = new PaymentFailedException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /** @test */
    public function exceptions_can_be_caught_separately()
    {
        $invalidHashCaught = false;
        $paymentFailedCaught = false;

        // Test InvalidHashException
        try {
            throw new InvalidHashException();
        } catch (InvalidHashException $e) {
            $invalidHashCaught = true;
        }

        // Test PaymentFailedException
        try {
            throw new PaymentFailedException();
        } catch (PaymentFailedException $e) {
            $paymentFailedCaught = true;
        }

        $this->assertTrue($invalidHashCaught);
        $this->assertTrue($paymentFailedCaught);
    }

    /** @test */
    public function exceptions_can_be_caught_together()
    {
        $exceptionCaught = false;

        try {
            throw new InvalidHashException();
        } catch (InvalidHashException | PaymentFailedException $e) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught);
    }
}