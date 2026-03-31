<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\CharacterController;
use App\Fixtures\DataFixtures\Factory\WeaponFactory;
use App\Fixtures\DataFixtures\Factory\BeingTalentFactory;
use App\Fixtures\DataFixtures\Factory\CharacterClassFactory;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\SkillFactory;
use App\Fixtures\DataFixtures\Factory\SpellFactory;
use App\Fixtures\DataFixtures\Factory\TalentFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(CharacterController::class)]
class CharacterControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testShowReturnsCharacterData(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        $kind = KindFactory::createOne(['name' => 'Human']);
        $characterClass = CharacterClassFactory::createOne(['name' => 'Warrior']);

        $character = CharacterFactory::createOne([
            'game' => $game,
            'name' => 'Aldric',
            'lastName' => 'Stoneheart',
            'token' => 'test-token-abc123',
            'level' => 5,
            'kind' => $kind,
            'characterClass' => $characterClass,
            'currentHealthPoints' => 80,
            'maxHealthPoints' => 100,
            'currentManaPoints' => 40,
            'maxManaPoints' => 60,
            'currentActionPoints' => 3,
            'maxActionPoints' => 5,
            'currentExhaustPoints' => 10,
            'maxExhaustPoints' => 20,
        ]);
        $client->request('GET', '/api/characters/test-token-abc123');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame($character->getId(), $data['id']);
        self::assertSame('test-token-abc123', $data['token']);
        self::assertSame('Aldric', $data['name']);
        self::assertSame('Stoneheart', $data['lastName']);
        self::assertSame(5, $data['level']);
        self::assertSame(['current' => 80, 'max' => 100], $data['health']);
        self::assertSame(['current' => 40, 'max' => 60], $data['mana']);
        self::assertSame(['current' => 3, 'max' => 5], $data['actionPoints']);
        self::assertSame(['current' => 10, 'max' => 20], $data['exhaustPoints']);
        self::assertSame($kind->getId(), $data['kind']['id']);
        self::assertSame('Human', $data['kind']['name']);
        self::assertSame($characterClass->getId(), $data['characterClass']['id']);
        self::assertSame('Warrior', $data['characterClass']['name']);
        self::assertSame([], $data['equipments']);
        self::assertSame([], $data['spells']);
        self::assertSame([], $data['skills']);
        self::assertSame([], $data['talents']);
    }

    public function testShowReturnsNullKindAndClassWhenNotSet(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne([
            'game' => $game,
            'token' => 'no-kind-token',
        ]);
        $client->request('GET', '/api/characters/no-kind-token');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertNull($data['kind']);
        self::assertNull($data['characterClass']);
    }

    public function testShowReturnsRelatedCollections(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        $spell = SpellFactory::createOne(['name' => 'Fireball']);
        $skill = SkillFactory::createOne(['name' => 'Backstab']);
        $talent = TalentFactory::createOne(['name' => 'Archery']);

        $character = CharacterFactory::createOne([
            'game' => $game,
            'token' => 'collections-token',
            'spells' => [$spell],
            'skills' => [$skill],
        ]);

        BeingTalentFactory::createOne([
            'being' => $character,
            'talent' => $talent,
            'value' => 7,
        ]);

        WeaponFactory::createOne([
            'game' => $game,
            'being' => $character,
            'name' => 'Iron Sword',
        ]);

        $client->request('GET', '/api/characters/collections-token');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertCount(1, $data['spells']);
        self::assertSame('Fireball', $data['spells'][0]['name']);

        self::assertCount(1, $data['skills']);
        self::assertSame('Backstab', $data['skills'][0]['name']);

        self::assertCount(1, $data['talents']);
        self::assertSame('Archery', $data['talents'][0]['name']);
        self::assertSame(7, $data['talents'][0]['value']);

        self::assertCount(1, $data['equipments']);
        self::assertSame('Iron Sword', $data['equipments'][0]['name']);
    }

    public function testShowReturns404WhenCharacterNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/characters/unknown-token');

        self::assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Character not found', $data['error']);
    }

    public function testDeleteRemovesCharacter(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne([
            'game' => $game,
            'token' => 'delete-token-abc',
        ]);

        $client->request('DELETE', '/api/characters/delete-token-abc');

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/characters/delete-token-abc');
        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteReturns404WhenCharacterNotFound(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/characters/unknown-token');

        self::assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Character not found', $data['error']);
    }
}
