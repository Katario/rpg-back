<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\User;
use App\Repository\ApiTokenRepository;
use App\Security\ApiTokenManager;

/**
 * Issues API access tokens in kernel/web tests.
 */
trait IssuesApiTokens
{
    private function issueApiToken(User $user, \DateTimeImmutable $now = new \DateTimeImmutable()): string
    {
        // Built by hand: the container may remove the service while nothing injects it.
        $manager = new ApiTokenManager(self::getContainer()->get(ApiTokenRepository::class));

        return $manager->issue($user, $now);
    }

    /**
     * @return array<string, string> server parameters for KernelBrowser::request()
     */
    private function bearer(string $plainToken): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$plainToken];
    }
}
