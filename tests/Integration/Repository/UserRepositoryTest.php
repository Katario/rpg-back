<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(UserRepository::class)]
class UserRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testPlayersAvailableForACharacterAreThePlayersWithoutOneInTheGame(): void
    {
        $game = GameFactory::createOne()->_real();
        $free = UserFactory::createOne(['email' => 'free@example.com'])->_real();
        $owner = UserFactory::createOne(['email' => 'owner@example.com'])->_real();
        UserFactory::createOne(['email' => 'stranger@example.com']); // not a player of the game
        $game->addMember($free);
        $game->addMember($owner);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        CharacterFactory::createOne(['game' => $game, 'user' => $owner]);

        $repository = self::getContainer()->get(UserRepository::class);

        self::assertSame(['free@example.com'], $this->emails($repository->playersAvailableForCharacterQueryBuilder($game->getId())->getQuery()->getResult()));
        self::assertSame(
            ['free@example.com', 'owner@example.com'],
            $this->emails($repository->playersAvailableForCharacterQueryBuilder($game->getId(), $owner->getId())->getQuery()->getResult()),
            'Editing a character keeps its owner in the choices.',
        );
    }

    /**
     * @param list<User> $users
     *
     * @return list<string>
     */
    private function emails(array $users): array
    {
        return array_map(static fn (User $user): string => $user->getEmail(), $users);
    }
}
