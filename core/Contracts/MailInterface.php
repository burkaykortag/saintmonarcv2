<?php

declare(strict_types=1);

namespace Core\Contracts;

interface MailInterface {
    public function to(string $address, string $name = ''): self;
    public function subject(string $subject): self;
    public function body(string $body, bool $isHtml = true): self;
    public function send(): bool;
}
