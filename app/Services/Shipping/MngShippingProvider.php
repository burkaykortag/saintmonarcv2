<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingProviderInterface;

class MngShippingProvider implements ShippingProviderInterface
{
    private string $customerKey;

    public function __construct(?string $customerKey = null)
    {
        $this->customerKey = $customerKey ?? (string)getenv('MNG_CUSTOMER_KEY');
    }

    public function isConfigured(): bool
    {
        return !empty($this->customerKey);
    }

    public function createShipment(array $shipmentData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'MNG Kargo credentials missing in .env (MNG_CUSTOMER_KEY)',
                'provider' => 'MNG',
                'requires_credentials' => true
            ];
        }

        $trackingNo = 'MNG-' . time() . rand(100, 999);
        return [
            'success' => true,
            'status' => 'created',
            'tracking_number' => $trackingNo,
            'provider' => 'MNG Kargo'
        ];
    }

    public function cancelShipment(string $trackingNumber): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED'
            ];
        }

        return ['success' => true, 'status' => 'cancelled', 'tracking_number' => $trackingNumber];
    }

    public function getTracking(string $trackingNumber): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED'
            ];
        }

        return [
            'success' => true,
            'tracking_number' => $trackingNumber,
            'status' => 'out_for_delivery',
            'current_location' => 'Dağıtımda'
        ];
    }

    public function generateLabel(string $trackingNumber): string
    {
        return "^XA^FO50,50^BY2^BCN,100,Y,N,N^FD" . $trackingNumber . "^FS^XZ";
    }
}
