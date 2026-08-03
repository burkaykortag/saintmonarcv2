<?php

declare(strict_types=1);

namespace App\Contracts;

interface PaymentGatewayInterface
{
    public function createPayment(array $paymentData): array;
    public function verifyPayment(array $callbackData): array;
    public function refundPayment(string $transactionReference, float $amount): array;
    public function getPaymentStatus(string $transactionReference): array;
}
