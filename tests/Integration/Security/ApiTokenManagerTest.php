<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security;

use App\Entity\ApiToken;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\ApiTokenRepository;
use App\Security\ApiTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(ApiTokenManager::class)]
#[CoversClass(ApiTokenRepository::class)]
class ApiTokenManagerTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testIssueStoresOnlyTheHashAndExpiresIn30Days(): void
    {
        $user = UserFactory::createOne()->_real();
        $now = new \DateTimeImmutable('2026-11-13 12:00:00');

        $plainToken = $this->manager()->issue($user, $now);

        self::assertStringStartsWith('rpg_', $plainToken);

        $repository = self::getContainer()->get(ApiTokenRepository::class);
        self::assertNull($repository->findOneBy(['tokenHash' => $plainToken]), 'The plain token must never be stored.');

        $apiToken = $repository->findOneByPlainToken($plainToken);
        self::assertInstanceOf(ApiToken::class, $apiToken);
        self::assertSame($user->getId(), $apiToken->getUser()->getId());
        self::assertEquals(new \DateTimeImmutable('2026-12-13 12:00:00'), $apiToken->getExpiresAt());
    }

    public function testEachIssueReturnsADifferentToken(): void
    {
        $user = UserFactory::createOne()->_real();
        $manager = $this->manager();

        self::assertNotSame($manager->issue($user), $manager->issue($user));
    }

    public function testUnknownTokenIsNotFound(): void
    {
        self::assertNull(self::getContainer()->get(ApiTokenRepository::class)->findOneByPlainToken('rpg_unknown'));
    }

    public function testDeleteExpiredKeepsValidTokens(): void
    {
        $user = UserFactory::createOne()->_real();
        $manager = $this->manager();
        $repository = self::getContainer()->get(ApiTokenRepository::class);

        $now = new \DateTimeImmutable('2026-11-13 12:00:00');
        $expired = $manager->issue($user, $now->modify('-31 days'));
        $valid = $manager->issue($user, $now);

        self::assertSame(1, $repository->deleteExpired($now));

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull($repository->findOneByPlainToken($expired));
        self::assertNotNull($repository->findOneByPlainToken($valid));
    }

    public function testTokensAreDeletedWithTheirUser(): void
    {
        $user = UserFactory::createOne();
        $plainToken = $this->manager()->issue($user->_real());

        $user->_delete();

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull(self::getContainer()->get(ApiTokenRepository::class)->findOneByPlainToken($plainToken));
    }

    /**
     * Built by hand: the service is not injected anywhere yet, so the container removes it.
     */
    private function manager(): ApiTokenManager
    {
        return new ApiTokenManager(self::getContainer()->get(ApiTokenRepository::class));
    }
}
