<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\SkillController;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\SkillFactory;
use App\ValueObject\DamageLine;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(SkillController::class)]
class SkillControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testListReturnsAllSkillsWithIsPassive(): void
    {
        $client = static::createClient();

        SkillFactory::createOne(['name' => 'Active Skill', 'damageLines' => [new DamageLine(2, 6, 3)]]);
        SkillFactory::createOne(['name' => 'Passive Skill', 'damageLines' => []]);

        $client->request('GET', '/api/skills');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertCount(2, $data);

        $byName = [];
        foreach ($data as $skill) {
            $byName[$skill['name']] = $skill;
        }

        self::assertFalse($byName['Active Skill']['isPassive']);
        self::assertNotEmpty($byName['Active Skill']['damageLines']);

        self::assertTrue($byName['Passive Skill']['isPassive']);
        self::assertSame([], $byName['Passive Skill']['damageLines']);
    }

    public function testAttachSkillToCharacterSucceeds(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'attach-token']);
        $skill = SkillFactory::createOne(['name' => 'Heavy Strike', 'damageLines' => [new DamageLine(2, 6, 0)]]);

        $client->request(
            'POST',
            '/api/characters/attach-token/skills',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['skillId' => $skill->getId()]),
        );

        self::assertResponseStatusCodeSame(201);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame($skill->getId(), $data['id']);
        self::assertSame('Heavy Strike', $data['name']);
        self::assertFalse($data['isPassive']);
    }

    public function testAttachReturnsConflictWhenAlreadyAttached(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'dup-token']);
        $skill = SkillFactory::createOne(['name' => 'Dodge', 'damageLines' => []]);

        $payload = (string) json_encode(['skillId' => $skill->getId()]);

        $client->request('POST', '/api/characters/dup-token/skills', server: ['CONTENT_TYPE' => 'application/json'], content: $payload);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/characters/dup-token/skills', server: ['CONTENT_TYPE' => 'application/json'], content: $payload);
        self::assertResponseStatusCodeSame(409);
    }

    public function testAttachReturnsNotFoundWhenSkillUnknown(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'unknown-skill-token']);

        $client->request(
            'POST',
            '/api/characters/unknown-skill-token/skills',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['skillId' => 999999]),
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testDetachSucceedsThenReturnsNotFound(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'detach-token']);
        $skill = SkillFactory::createOne(['name' => 'Parry', 'damageLines' => []]);

        $client->request(
            'POST',
            '/api/characters/detach-token/skills',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['skillId' => $skill->getId()]),
        );
        self::assertResponseStatusCodeSame(201);

        $client->request('DELETE', '/api/characters/detach-token/skills/'.$skill->getId());
        self::assertResponseStatusCodeSame(204);

        $client->request('DELETE', '/api/characters/detach-token/skills/'.$skill->getId());
        self::assertResponseStatusCodeSame(404);
    }
}
