<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\Attributes\Before;

/**
 * Clears the rate limiter state (login throttling, registration) before each test.
 *
 * The state lives in the filesystem cache, so it would otherwise leak between tests and runs.
 * An in-memory pool is not an option: the kernel resets it between requests, even with disableReboot().
 */
trait ResetsRateLimiters
{
    #[Before]
    protected function clearRateLimiters(): void
    {
        self::getContainer()->get('cache.rate_limiter')->clear();

        // WebTestCase::createClient() refuses an already booted kernel.
        self::ensureKernelShutdown();
    }
}
