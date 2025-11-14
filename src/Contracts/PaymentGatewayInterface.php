<?php

namespace Aldiazhar\PaymentGateways\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Prepare payment inputs/form data
     *
     * @param array $payload Payment data
     * @return array
     */
    public function inputs(array $payload): array;

    /**
     * Get payment gateway URL
     *
     * @return string
     */
    public function url(): string;

    /**
     * Check transaction status
     *
     * @param string $orderId
     * @return array
     */
    public function check(string $orderId): array;

    /**
     * Verify payment callback/return signature (returns boolean)
     *
     * @param array $data
     * @return bool
     */
    public function verify(array $data): bool;

    /**
     * Verify payment signature and throw exception if invalid
     *
     * @param array $data
     * @throws \Aldiazhar\PaymentGateways\Exceptions\InvalidHashException
     * @return void
     */
    public function verifyOrFail(array $data): void;

    /**
     * Ensure payment is successful and throw exception if not
     *
     * @param array $data
     * @throws \Aldiazhar\PaymentGateways\Exceptions\PaymentFailedException
     * @return void
     */
    public function ensureSuccess(array $data): void;

    /**
     * Verify signature and ensure payment success in one call
     *
     * @param array $data
     * @throws \Aldiazhar\PaymentGateways\Exceptions\InvalidHashException
     * @throws \Aldiazhar\PaymentGateways\Exceptions\PaymentFailedException
     * @return void
     */
    public function validatePayment(array $data): void;

    /**
     * Switch to specific account configuration
     *
     * @param string|null $account Account name or null for default
     * @return self
     */
    public function account(?string $account = null): self;

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get current configuration
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Get current account name
     *
     * @return string
     */
    public function getCurrentAccount(): string;
}