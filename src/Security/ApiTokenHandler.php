<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Resolves the user behind an `Authorization: Bearer <token>` header (Symfony access_token authenticator).
 */
class ApiTokenHandler implements AccessTokenHandlerInterface
{
    private const string LAST_USED_REFRESH_INTERVAL = '-5 minutes';

    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $apiToken = $this->apiTokenRepository->findOneByPlainToken($accessToken);
        $now = new \DateTimeImmutable();

        if (!$apiToken || $apiToken->isExpired($now)) {
            throw new BadCredentialsException('Invalid or expired access token.');
        }

        $lastUsedAt = $apiToken->getLastUsedAt();
        if (!$lastUsedAt || $lastUsedAt < $now->modify(self::LAST_USED_REFRESH_INTERVAL)) {
            $apiToken->markUsed($now);
            $this->em->flush();
        }

        $user = $apiToken->getUser();

        return new UserBadge($user->getUserIdentifier(), fn () => $user);
    }
}
