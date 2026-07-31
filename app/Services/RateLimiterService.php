<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\CacheInterface;

class RateLimiterService
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Check if an action key has exceeded the rate limit
     */
    public function tooManyAttempts(string $key, int $maxAttempts = 5, int $decaySeconds = 60): bool
    {
        $attemptsKey = "rate_limit_" . md5($key);
        $attempts = (int)$this->cache->get($attemptsKey, 0);

        return $attempts >= $maxAttempts;
    }

    /**
     * Hit the rate limit for a key
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        $attemptsKey = "rate_limit_" . md5($key);
        $attempts = (int)$this->cache->get($attemptsKey, 0) + 1;
        $this->cache->set($attemptsKey, $attempts, $decaySeconds);

        return $attempts;
    }

    /**
     * Clear rate limit attempts for a key
     */
    public function clear(string $key): void
    {
        $attemptsKey = "rate_limit_" . md5($key);
        $this->cache->delete($attemptsKey);
    }
}
