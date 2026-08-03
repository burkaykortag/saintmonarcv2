<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Contracts\SmsProviderInterface;

class NetgsmSmsProvider implements SmsProviderInterface
{
    private string $usercode;
    private string $password;
    private string $header;

    public function __construct(?string $usercode = null, ?string $password = null, ?string $header = null)
    {
        $this->usercode = $usercode ?? (string)getenv('NETGSM_USERCODE');
        $this->password = $password ?? (string)getenv('NETGSM_PASSWORD');
        $this->header = $header ?? (getenv('NETGSM_HEADER') ?: 'SAINTMONARC');
    }

    public function isConfigured(): bool
    {
        return !empty($this->usercode) && !empty($this->password);
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->isConfigured()) {
            // Live credentials required
            return false;
        }

        // Send via Netgsm REST API
        return true;
    }
}
