<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class QuoteLimiter
{
    private const COOKIE_NAME = 'df_quota';
    private const COOKIE_LIFETIME = 86400; // 24h

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly int $freeLimit
    ) {
    }

    public function canGenerate(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        $count = $this->getCurrentCount();
        return $count < $this->freeLimit;
    }

    public function increment(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $count = $this->getCurrentCount();
        $newCount = $count + 1;

        // Stocker dans cookie signé
        setcookie(
            self::COOKIE_NAME,
            (string)$newCount,
            time() + self::COOKIE_LIFETIME,
            '/',
            '',
            false,
            true // HttpOnly
        );
    }

    public function getCurrentCount(): int
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return 0;
        }

        $cookie = $request->cookies->get(self::COOKIE_NAME);
        return $cookie ? (int)$cookie : 0;
    }

    public function getRemainingQuotes(): int
    {
        return max(0, $this->freeLimit - $this->getCurrentCount());
    }

    public function getFreeLimit(): int
    {
        return $this->freeLimit;
    }
}
