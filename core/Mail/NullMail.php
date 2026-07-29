<?php

declare(strict_types=1);

namespace Core\Mail;

use Core\Contracts\MailInterface;
use Core\Contracts\LoggerInterface;

class NullMail implements MailInterface {
    private LoggerInterface $logger;
    private array $message = [];

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function to(string $address, string $name = ''): self {
        $this->message['to'] = ['address' => $address, 'name' => $name];
        return $this;
    }

    public function subject(string $subject): self {
        $this->message['subject'] = $subject;
        return $this;
    }

    public function body(string $body, bool $isHtml = true): self {
        $this->message['body'] = $body;
        $this->message['isHtml'] = $isHtml;
        return $this;
    }

    public function send(): bool {
        $this->logger->info("NullMail pretending to send email.", $this->message);
        return true;
    }
}
