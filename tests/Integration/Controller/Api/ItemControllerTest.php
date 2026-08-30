<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\ItemController;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\ItemFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(ItemController::class)]
class ItemControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testListReturnsAllItemsWithWeight(): void
    {
        $client = static::createClient();

        ItemFactory::createOne(['name' => 'Potion', 'weight' => 500, 'value' => 10]);
        ItemFactory::createOne(['name' => 'Rope', 'weight' => 2000, 'value' => 5]);

        $client->request('GET', '/api/items');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertCount(2, $data);

        $byName = [];
        foreach ($data as $item) {
            $byName[$item['name']] = $item;
        }

        self::assertSame(500, $byName['Potion']['weight']);
        self::assertSame(2000, $byName['Rope']['weight']);
    }

    public function testAttachItemReturnsFullPayload(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'attach-item-token']);
        $item = ItemFactory::createOne(['name' => 'Potion', 'weight' => 500]);

        $client->request(
            'POST',
            '/api/characters/attach-item-token/items',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['itemId' => $item->getId(), 'quantity' => 3]),
        );

        self::assertResponseStatusCodeSame(201);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame($item->getId(), $data['id']);
        self::assertSame('Potion', $data['name']);
        self::assertSame(500, $data['weight']);
        self::assertSame(3, $data['quantity']);
        self::assertArrayHasKey('currentLoadPoints', $data);
    }

    public function testAttachDefaultsQuantityToOne(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'default-qty-token']);
        $item = ItemFactory::createOne(['name' => 'Torch', 'weight' => 200]);

        $client->request(
            'POST',
            '/api/characters/default-qty-token/items',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['itemId' => $item->getId()]),
        );

        self::assertResponseStatusCodeSame(201);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(1, $data['quantity']);
    }

    public function testAttachReturnsConflictWhenAlreadyAttached(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'dup-item-token']);
        $item = ItemFactory::createOne(['name' => 'Lockpick', 'weight' => 50]);

        $payload = (string) json_encode(['itemId' => $item->getId()]);

        $client->request('POST', '/api/characters/dup-item-token/items', server: ['CONTENT_TYPE' => 'application/json'], content: $payload);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/characters/dup-item-token/items', server: ['CONTENT_TYPE' => 'application/json'], content: $payload);
        self::assertResponseStatusCodeSame(409);
    }

    public function testAttachReturnsBadRequestWhenItemIdMissing(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'no-id-token']);

        $client->request(
            'POST',
            '/api/characters/no-id-token/items',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['quantity' => 2]),
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testAttachReturnsNotFoundWhenItemUnknown(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'unknown-item-token']);

        $client->request(
            'POST',
            '/api/characters/unknown-item-token/items',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['itemId' => 999999]),
        );

        self::assertResponseStatusCodeSame(404);
    }
}
