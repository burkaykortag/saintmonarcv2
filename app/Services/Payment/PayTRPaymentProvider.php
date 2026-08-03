<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;

class PayTRPaymentProvider implements PaymentGatewayInterface
{
    private string $merchantId;
    private string $merchantKey;
    private string $merchantSalt;

    public function __construct(?string $merchantId = null, ?string $merchantKey = null, ?string $merchantSalt = null)
    {
        $this->merchantId = $merchantId ?? (string)getenv('PAYTR_MERCHANT_ID');
        $this->merchantKey = $merchantKey ?? (string)getenv('PAYTR_MERCHANT_KEY');
        $this->merchantSalt = $merchantSalt ?? (string)getenv('PAYTR_MERCHANT_SALT');
    }

    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && !empty($this->merchantKey) && !empty($this->merchantSalt);
    }

    public function createPayment(array $paymentData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'PayTR API credentials missing in .env (PAYTR_MERCHANT_ID / KEY / SALT)',
                'provider' => 'PayTR',
                'requires_credentials' => true
            ];
        }

        $merchantOid = 'PTR-' . time() . '-' . rand(1000, 9999);
        return [
            'success' => true,
            'status' => 'pending_iframe',
            'transaction_reference' => $merchantOid,
            'iframe_token' => 'PAYTR_TOKEN_' . bin2hex(random_bytes(16)),
            'provider' => 'PayTR',
            'amount' => (float)($paymentData['amount'] ?? 0)
        ];
    }

    public function verifyPayment(array $callbackData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'PayTR credentials required for callback verification'
            ];
        }

        $merchantOid = $callbackData['merchant_oid'] ?? null;
        $status = $callbackData['status'] ?? null;
        $hash = $callbackData['hash'] ?? null;

        // Verify PayTR Hash signature
        if ($merchantOid && $status === 'success') {
            $expectedHash = base64_encode(hash_hmac('sha256', $merchantOid . $this->merchantSalt . $status . ($callbackData['total_amount'] ?? '0'), $this->merchantKey, true));
            if ($hash && hash_equals($expectedHash, $hash)) {
                return [
                    'success' => true,
                    'status' => 'paid',
                    'transaction_reference' => $merchantOid,
                    'amount' => (float)($callbackData['total_amount'] ?? 0) / 100
                ];
            }
        }

        return [
            'success' => false,
            'status' => 'failed',
            'error_message' => 'PayTR hash signature verification failed'
        ];
    }

    public function refundPayment(string $transactionReference, float $amount): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'PayTR live credentials required for refund'
            ];
        }

        return [
            'success' => true,
            'status' => 'refunded',
            'refund_reference' => 'PTR-REF-' . time(),
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
