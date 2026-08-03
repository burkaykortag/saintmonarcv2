<?php

declare(strict_types=1);

namespace App\Contracts;

interface ShippingProviderInterface
{
    public function createShipment(array $shipmentData): array;
    public function cancelShipment(string $trackingNumber): array;
    public function getTracking(string $trackingNumber): array;
    public function generateLabel(string $trackingNumber): string;
}
