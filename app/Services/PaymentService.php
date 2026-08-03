<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\IyzicoPaymentProvider;
use App\Services\Payment\PayTRPaymentProvider;
use App\Services\Payment\SipayPaymentProvider;
use Core\Contracts\DatabaseInterface;

class PaymentService
{
    private DatabaseInterface $db;
    private PaymentGatewayInterface $provider;

    public function __construct(DatabaseInterface $db, ?PaymentGatewayInterface $provider = null)
    {
        $this->db = $db;
        $this->provider = $provider ?? $this->resolveProvider();
    }

    public function resolveProvider(): PaymentGatewayInterface
    {
        $driver = strtolower((string)(getenv('PAYMENT_PROVIDER') ?: 'iyzico'));
        switch ($driver) {
            case 'paytr':
                return new PayTRPaymentProvider();
            case 'sipay':
                return new SipayPaymentProvider();
            case 'iyzico':
            default:
                return new IyzicoPaymentProvider();
        }
    }

    public function getProvider(): PaymentGatewayInterface
    {
        return $this->provider;
    }

    public function initiatePayment(int $orderId, float $amount, array $extraData = []): array
    {
        $paymentData = array_merge([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'TRY'
        ], $extraData);

        return $this->provider->createPayment($paymentData);
    }

    /**
     * Handle webhook/callback with Idempotency Protection.
     * Prevents duplicate financial ledger postings if identical transaction callback arrives twice.
     */
    public function handleCallback(array $callbackData): array
    {
        $txRef = $callbackData['transaction_reference'] ?? $callbackData['paymentId'] ?? $callbackData['merchant_oid'] ?? null;
        
        if ($txRef) {
            // Check if transaction is already processed & paid
            $existing = $this->db->query(
                "SELECT * FROM payment_transactions WHERE transaction_reference = :ref AND status = 'paid' LIMIT 1",
                [':ref' => $txRef]
            );

            if (!empty($existing)) {
                return [
                    'success' => true,
                    'status' => 'already_processed',
                    'message' => 'Callback already processed idempotently. No duplicate ledger entry created.',
                    'transaction_id' => $existing[0]['id'],
                    'transaction_reference' => $txRef
                ];
            }
        }

        $result = $this->provider->verifyPayment($callbackData);

        if ($result['success'] && ($result['status'] === 'paid' || $result['status'] === 'completed')) {
            $orderId = (int)($callbackData['order_id'] ?? 0);
            $amount = (float)($result['amount'] ?? 0);

            // Record transaction record
            $this->db->execute(
                "INSERT INTO payment_transactions (order_id, payment_method_id, amount, status, transaction_reference, created_at) 
                 VALUES (:oid, 1, :amount, 'paid', :ref, NOW())",
                [
                    ':oid' => $orderId ?: null,
                    ':amount' => $amount,
                    ':ref' => $txRef ?: ('TX-' . microtime(true))
                ]
            );

            // Update order status if orderId valid
            if ($orderId > 0) {
                $this->db->execute(
                    "UPDATE orders SET status = 'confirmed', updated_at = NOW() WHERE id = :id",
                    [':id' => $orderId]
                );
            }
        }

        return $result;
    }
}
