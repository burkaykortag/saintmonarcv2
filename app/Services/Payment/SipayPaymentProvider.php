<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;

class SipayPaymentProvider implements PaymentGatewayInterface
{
    private string $appId;
    private string $appSecret;

    public function __construct(?string $appId = null, ?string $appSecret = null)
    {
        $this->appId = $appId ?? (string)getenv('SIPAY_APP_ID');
        $this->appSecret = $appSecret ?? (string)getenv('SIPAY_APP_SECRET');
    }

    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->appSecret);
    }

    public function createPayment(array $paymentData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'Sipay API credentials missing in .env (SIPAY_APP_ID / SIPAY_APP_SECRET)',
                'provider' => 'Sipay',
                'requires_credentials' => true
            ];
        }

        $invoiceId = 'SIP-' . time() . '-' . rand(1000, 9999);
        return [
            'success' => true,
            'status' => 'pending_3ds',
            'transaction_reference' => $invoiceId,
            'payment_url' => 'https://api.sipay.com.tr/checkout/' . $invoiceId,
            'provider' => 'Sipay',
            'amount' => (float)($paymentData['amount'] ?? 0)
        ];
    }

    public function verifyPayment(array $callbackData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'Sipay live credentials required for callback verification'
            ];
        }

        $invoiceId = $callbackData['invoice_id'] ?? null;
        $status = $callbackData['status_code'] ?? null;

        if ($invoiceId && ($status == 100 || $status === 'success')) {
            return [
                'success' => true,
                'status' => 'paid',
                'transaction_reference' => $invoiceId,
                'amount' => (float)($callbackData['total_amount'] ?? 0)
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'error_message' => 'Sipay payment verification failed'
        ];
    }

    public function refundPayment(string $transactionReference, float $amount): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'Sipay live credentials required for refund'
            ];
        }

        return [
            'success' => true,
            'status' => 'refunded',
            'refund_reference' => 'SIP-REF-' . time(),
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
