<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\WeaponController;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(WeaponController::class)]
class WeaponControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testAddWeaponExposesIsPassiveForEachSkill(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne([
            'game' => $game,
            'token' => 'weapon-token',
        ]);

        $payload = [
            'name' => 'Longsword',
            'description' => '',
            'weight' => 1500,
            'currentDurabilityPoints' => 20,
            'maxDurabilityPoints' => 20,
            'isEquipped' => false,
            'skills' => [
                [
                    'name' => 'Heavy Strike',
                    'exhaustPointCost' => 2,
                    'actionPointCost' => 1,
                    'damageLines' => [
                        ['diceCount' => 2, 'diceFaces' => 6, 'fixedAmount' => 3, 'type' => 'physical', 'element' => null],
                    ],
                ],
                [
                    'name' => 'Steady Stance',
                    'exhaustPointCost' => 0,
                    'actionPointCost' => 0,
                ],
            ],
        ];

        $client->request('POST', '/api/characters/weapon-token/weapons', server: ['CONTENT_TYPE' => 'application/json'], content: (string) json_encode($payload));

        self::assertResponseStatusCodeSame(201);

        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertCount(2, $data['skills']);

        $bySkillName = [];
        foreach ($data['skills'] as $skill) {
            $bySkillName[$skill['name']] = $skill;
        }

        self::assertArrayHasKey('isPassive', $bySkillName['Heavy Strike']);
        self::assertFalse($bySkillName['Heavy Strike']['isPassive']);
        self::assertNotEmpty($bySkillName['Heavy Strike']['damageLines']);

        self::assertArrayHasKey('isPassive', $bySkillName['Steady Stance']);
        self::assertTrue($bySkillName['Steady Stance']['isPassive']);
        self::assertSame([], $bySkillName['Steady Stance']['damageLines']);
    }

    public function testUpdateWeaponPersistsCurrentDurabilityPoints(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne([
            'game' => $game,
            'token' => 'patch-token',
            'kind' => KindFactory::createOne(),
        ]);

        $client->request(
            'POST',
            '/api/characters/patch-token/weapons',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'name' => 'Dagger',
                'currentDurabilityPoints' => 10,
                'maxDurabilityPoints' => 10,
            ]),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode((string) $client->getResponse()->getContent(), true);
        $weaponId = $created['id'];

        $client->request(
            'PATCH',
            "/api/characters/patch-token/weapons/{$weaponId}",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['currentDurabilityPoints' => 3]),
        );
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/characters/patch-token');
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        $weapon = null;
        foreach ($data['weapons'] as $w) {
            if ($w['id'] === $weaponId) {
                $weapon = $w;
                break;
            }
        }
        self::assertNotNull($weapon);
        self::assertSame(3, $weapon['currentDurabilityPoints']);
    }
}
