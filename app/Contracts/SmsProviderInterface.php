<?php

declare(strict_types=1);

namespace App\Contracts;

interface SmsProviderInterface
{
    public function send(string $phone, string $message): bool;
}
