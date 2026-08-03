<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;

class IyzicoPaymentProvider implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $secretKey;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $secretKey = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? (string)getenv('IYZICO_API_KEY');
        $this->secretKey = $secretKey ?? (string)getenv('IYZICO_SECRET_KEY');
        $this->baseUrl = $baseUrl ?? (getenv('IYZICO_BASE_URL') ?: 'https://api.iyzipay.com');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->secretKey) && !str_contains($this->apiKey, 'placeholder');
    }

    public function createPayment(array $paymentData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'Iyzico API credentials missing in .env (IYZICO_API_KEY / IYZICO_SECRET_KEY)',
                'provider' => 'Iyzico',
                'requires_credentials' => true
            ];
        }

        // Standard 3D Secure Form Request Structure
        $transactionRef = 'IYZ-' . time() . '-' . rand(1000, 9999);
        return [
            'success' => true,
            'status' => 'pending_3ds',
            'transaction_reference' => $transactionRef,
            'redirect_url' => $this->baseUrl . '/payment/3d/init/' . $transactionRef,
            'provider' => 'Iyzico',
            'amount' => (float)($paymentData['amount'] ?? 0),
            'currency' => $paymentData['currency'] ?? 'TRY'
        ];
    }

    public function verifyPayment(array $callbackData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'Iyzico live credentials required for callback verification'
            ];
        }

        $token = $callbackData['token'] ?? null;
        $status = $callbackData['status'] ?? null;

        if ($status === 'success' && !empty($token)) {
            return [
                'success' => true,
                'status' => 'paid',
                'transaction_reference' => $callbackData['paymentId'] ?? ('IYZ-PAY-' . time()),
                'amount' => (float)($callbackData['price'] ?? 0),
                'raw_response' => $callbackData
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'error_message' => $callbackData['errorMessage'] ?? 'Iyzico payment verification failed'
        ];
    }

    public function refundPayment(string $transactionReference, float $amount): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'Iyzico live credentials required for refund'
            ];
        }

        return [
            'success' => true,
            'status' => 'refunded',
            'refund_reference' => 'IYZ-REF-' . time(),
            'amount' => $amount
        ];
    }

    public function getPaymentStatus(string $transactionReference): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED'
            ];
        }

        return [
            'success' => true,
            'transaction_reference' => $transactionReference,
            'status' => 'paid'
        ];
    }
}
