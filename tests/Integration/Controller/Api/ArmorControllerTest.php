<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\ArmorController;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(ArmorController::class)]
class ArmorControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testAddArmorExposesIsPassiveForEachSkill(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne([
            'game' => $game,
            'token' => 'armor-token',
        ]);

        $payload = [
            'name' => 'Plate Cuirass',
            'description' => '',
            'weight' => 5000,
            'currentDurabilityPoints' => 30,
            'maxDurabilityPoints' => 30,
            'isEquipped' => false,
            'skills' => [
                [
                    'name' => 'Counter Slash',
                    'exhaustPointCost' => 1,
                    'actionPointCost' => 1,
                    'damageLines' => [
                        ['diceCount' => 1, 'diceFaces' => 8, 'fixedAmount' => 0, 'type' => 'physical', 'element' => null],
                    ],
                ],
                [
                    'name' => 'Sturdy Build',
                    'exhaustPointCost' => 0,
                    'actionPointCost' => 0,
                ],
            ],
        ];

        $client->request('POST', '/api/characters/armor-token/armors', server: ['CONTENT_TYPE' => 'application/json'], content: (string) json_encode($payload));

        self::assertResponseStatusCodeSame(201);

        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertCount(2, $data['skills']);

        $bySkillName = [];
        foreach ($data['skills'] as $skill) {
            $bySkillName[$skill['name']] = $skill;
        }

        self::assertArrayHasKey('isPassive', $bySkillName['Counter Slash']);
        self::assertFalse($bySkillName['Counter Slash']['isPassive']);
        self::assertNotEmpty($bySkillName['Counter Slash']['damageLines']);

        self::assertArrayHasKey('isPassive', $bySkillName['Sturdy Build']);
        self::assertTrue($bySkillName['Sturdy Build']['isPassive']);
        self::assertSame([], $bySkillName['Sturdy Build']['damageLines']);
    }
}
