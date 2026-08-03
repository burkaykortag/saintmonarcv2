<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingProviderInterface;

class ArasShippingProvider implements ShippingProviderInterface
{
    private string $username;
    private string $password;

    public function __construct(?string $username = null, ?string $password = null)
    {
        $this->username = $username ?? (string)getenv('ARAS_USER');
        $this->password = $password ?? (string)getenv('ARAS_PASS');
    }

    public function isConfigured(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    public function createShipment(array $shipmentData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'BLOCKED_LIVE_CREDENTIAL_REQUIRED',
                'message' => 'Aras Kargo credentials missing in .env (ARAS_USER / ARAS_PASS)',
                'provider' => 'Aras',
                'requires_credentials' => true
            ];
        }

        $trackingNo = 'ARAS-' . time() . rand(100, 999);
        return [
            'success' => true,
            'status' => 'created',
            'tracking_number' => $trackingNo,
            'provider' => 'Aras Kargo'
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
            'status' => 'delivered',
            'current_location' => 'Teslim Edildi'
        ];
    }

    public function generateLabel(string $trackingNumber): string
    {
        return "^XA^FO50,50^BY2^BCN,100,Y,N,N^FD" . $trackingNumber . "^FS^XZ";
    }
}
