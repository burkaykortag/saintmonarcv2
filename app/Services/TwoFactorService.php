<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;

class TwoFactorService {
    private DatabaseInterface $db;
    private CacheInterface $cache;

    public function __construct(DatabaseInterface $db, CacheInterface $cache) {
        $this->db = $db;
        $this->cache = $cache;
    }

    public function generate2FaEmailCode(int $userId): string {
        $code = (string)random_int(100000, 999999);
        $this->cache->set("2fa_email_code_{$userId}", $code, 300);
        return $code;
    }

    public function verify2FaEmailCode(int $userId, string $code): bool {
        $storedCode = $this->cache->get("2fa_email_code_{$userId}");
        if ($storedCode && $storedCode === $code) {
            $this->cache->delete("2fa_email_code_{$userId}");
            return true;
        }
        return false;
    }

    public function verifyAuthenticatorCode(string $secret, string $code): bool {
        $timeSlice = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            $calculated = $this->calculateTotpCode($secret, $timeSlice + $i);
            if ($calculated === $code) {
                return true;
            }
        }
        return false;
    }

    private function calculateTotpCode(string $secret, float $timeSlice): string {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hmac = hash_hmac('sha1', $time, $key, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashpart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashpart)[1] & 0x7FFFFFFF;
        $value = $value % 1000000;
        return str_pad((string)$value, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $buf = '';
        $val = 0;
        $len = 0;
        for ($i = 0; $i < strlen($secret); $i++) {
            $c = $secret[$i];
            $idx = strpos($alphabet, $c);
            if ($idx === false) {
                continue;
            }
            $val = ($val << 5) | $idx;
            $len += 5;
            while ($len >= 8) {
                $len -= 8;
                $buf .= chr(($val >> $len) & 0xFF);
            }
        }
        return $buf;
    }
}
