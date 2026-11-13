<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;

/**
 * Issues API access tokens. The plain token is returned once to be sent to the client;
 * only its hash is persisted.
 */
class ApiTokenManager
{
    public const string LIFETIME = '+30 days';

    private const string PREFIX = 'rpg_';

    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
    ) {
    }

    /**
     * @return string the plain token — the only time it is available
     */
    public function issue(User $user, \DateTimeImmutable $now = new \DateTimeImmutable()): string
    {
        $plainToken = self::PREFIX.bin2hex(random_bytes(32));

        $this->apiTokenRepository->save(
            new ApiToken($user, ApiToken::hash($plainToken), $now->modify(self::LIFETIME), $now),
        );

        return $plainToken;
    }
}
