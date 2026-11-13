<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\ExportController;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\SpellFactory;
use App\Fixtures\DataFixtures\Factory\WeaponFactory;
use App\Serializer\CharacterSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(ExportController::class)]
#[CoversClass(CharacterSerializer::class)]
class ExportControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testExportReturnsSerializedCharacterAndMatchesSheetPayload(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        $kind = KindFactory::createOne(['name' => 'Human']);
        $spell = SpellFactory::createOne(['name' => 'Fireball']);
        $character = CharacterFactory::createOne([
            'game' => $game,
            'token' => 'export-token',
            'name' => 'Aldric',
            'kind' => $kind,
            'spells' => [$spell],
        ]);
        WeaponFactory::createOne(['game' => $game, 'being' => $character, 'name' => 'Iron Sword']);

        $client->request('GET', '/api/characters/export-token/export');

        self::assertResponseIsSuccessful();
        $export = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertSame('Aldric', $export['name']);
        self::assertSame('Human', $export['kind']['name']);
        self::assertCount(1, $export['weapons']);
        self::assertSame('Iron Sword', $export['weapons'][0]['name']);
        self::assertCount(1, $export['spells']);
        self::assertSame('Fireball', $export['spells'][0]['name']);

        // The export shares the exact same serializer as the character sheet endpoint:
        // the two payloads must be byte-for-byte identical.
        $client->request('GET', '/api/characters/export-token');
        $sheet = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertSame($sheet, $export);
    }

    public function testExportReturns404WhenCharacterNotFound(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/characters/unknown-token/export');

        self::assertResponseStatusCodeSame(404);
    }

    public function testExportReturns422WhenCharacterHasNoKind(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'export-no-kind-token']);

        $client->request('GET', '/api/characters/export-no-kind-token/export');

        self::assertResponseStatusCodeSame(422);
    }
}
