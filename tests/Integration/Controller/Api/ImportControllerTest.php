<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\ImportController;
use App\Entity\BeingItem;
use App\Entity\Item;
use App\Entity\Skill;
use App\Entity\Talent;
use App\Fixtures\DataFixtures\Factory\BeingTalentFactory;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\SkillFactory;
use App\Fixtures\DataFixtures\Factory\SpellFactory;
use App\Fixtures\DataFixtures\Factory\TalentFactory;
use App\Fixtures\DataFixtures\Factory\WeaponFactory;
use App\Serializer\CharacterDeserializer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(ImportController::class)]
#[CoversClass(CharacterDeserializer::class)]
class ImportControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testExportImportRoundTripIsIdentical(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $game = GameFactory::createOne();
        $kind = KindFactory::createOne(['name' => 'Human']);
        $primaryTalent = TalentFactory::createOne(['name' => 'Alchimie']);
        $secondaryTalent = TalentFactory::createOne(['name' => 'Precision']);
        $ownedTalent = TalentFactory::createOne(['name' => 'Athletisme']);
        $spell = SpellFactory::createOne(['name' => 'Fireball']);
        $skill = SkillFactory::createOne(['name' => 'Meditation']);

        $character = CharacterFactory::createOne([
            'game' => $game,
            'token' => 'roundtrip-token',
            'name' => 'Aldric',
            'avatarUrl' => null,
            'kind' => $kind,
            'spells' => [$spell],
            'skills' => [$skill],
        ]);

        WeaponFactory::createOne(['game' => $game, 'being' => $character, 'name' => 'Iron Sword', 'weight' => 1500]);
        BeingTalentFactory::createOne(['being' => $character, 'talent' => $ownedTalent, 'value' => 7]);

        $real = $character->_real();
        $real->addPrimaryTalent($primaryTalent->_real());
        $real->addSecondaryTalent($secondaryTalent->_real());
        $item = new Item()
            ->setName('Potion')
            ->setDescription('Heals')
            ->setValue(10)
            ->setWeight(500)
            ->setIsReady(true)
            ->setIsPrivate(false);
        $em->persist($item);
        $em->persist(new BeingItem()->setBeing($real)->setItem($item)->setQuantity(3));
        $em->flush();

        // Export the original character.
        $client->request('GET', '/api/characters/roundtrip-token/export');
        self::assertResponseIsSuccessful();
        $exported = json_decode((string) $client->getResponse()->getContent(), true);

        // Free the token, then import the exported payload back.
        $client->request('DELETE', '/api/characters/roundtrip-token');
        self::assertResponseStatusCodeSame(204);

        $client->request(
            'POST',
            '/api/characters/import',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['gameId' => $game->getId(), 'data' => $exported]),
        );
        self::assertResponseStatusCodeSame(201);
        $reimported = json_decode((string) $client->getResponse()->getContent(), true);

        // Everything round-trips identically except the auto-generated database ids.
        self::assertSame($this->stripIds($exported), $this->stripIds($reimported));
        self::assertSame('roundtrip-token', $reimported['token']);
        self::assertSame('Iron Sword', $reimported['weapons'][0]['name']);
        self::assertSame(1500, $reimported['weapons'][0]['weight']);
        self::assertSame(3, $reimported['items'][0]['quantity']);
        self::assertSame(7, $reimported['talents'][0]['value']);
        self::assertSame('Alchimie', $reimported['primaryTalents'][0]['name']);
    }

    public function testImportOnEmptyEncyclopediaCreatesEachEntryOnceWithAllTalentLevels(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $game = GameFactory::createOne();

        // Every encyclopedia entry is missing from the database, and the same names appear in
        // several places of the payload: they must be created once and reused afterwards.
        $slash = ['name' => 'Slash', 'description' => 'A cut', 'exhaustPointCost' => 1, 'actionPointCost' => 2, 'damageLines' => []];
        $payload = [
            'token' => 'fresh-import-token',
            'name' => 'Nip',
            'kind' => [
                'name' => 'Elf',
                'bonuses' => [['type' => 'talent', 'talent' => ['name' => 'Destruction'], 'value' => 2]],
            ],
            'weapons' => [['name' => 'Dagger', 'weight' => 800, 'damageLines' => [], 'skills' => [$slash]]],
            'skills' => [$slash],
            'items' => [
                ['name' => 'Potion', 'description' => 'Heals', 'value' => 10, 'weight' => 500, 'quantity' => 1],
            ],
            'talents' => [[
                'name' => 'Destruction',
                'value' => 5,
                'talentLevels' => [
                    ['tier' => 'novice', 'requiredPoints' => 1, 'description' => 'Novice spells'],
                ],
                'allTalentLevels' => [
                    ['tier' => 'novice', 'requiredPoints' => 1, 'description' => 'Novice spells'],
                    ['tier' => 'companion', 'requiredPoints' => 10, 'description' => 'Companion spells'],
                ],
            ]],
            'primaryTalents' => [['name' => 'Destruction']],
        ];

        $client->request(
            'POST',
            '/api/characters/import',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['gameId' => $game->getId(), 'data' => $payload]),
        );
        self::assertResponseStatusCodeSame(201);
        $response = json_decode((string) $client->getResponse()->getContent(), true);

        $talents = $em->getRepository(Talent::class)->findBy(['name' => 'Destruction']);
        self::assertCount(1, $talents);
        self::assertCount(1, $em->getRepository(Skill::class)->findBy(['name' => 'Slash']));

        // Locked levels are kept on the recreated encyclopedia entry, not only the unlocked ones.
        self::assertCount(2, $talents[0]->getTalentLevels());
        self::assertCount(1, $response['talents'][0]['talentLevels']);
        self::assertCount(2, $response['talents'][0]['allTalentLevels']);
    }

    public function testImportReturns409WhenTokenAlreadyExists(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        $kind = KindFactory::createOne(['name' => 'Human']);
        CharacterFactory::createOne(['game' => $game, 'token' => 'existing-token', 'kind' => $kind]);

        $client->request(
            'POST',
            '/api/characters/import',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'gameId' => $game->getId(),
                'data' => ['token' => 'existing-token', 'name' => 'Copy', 'kind' => ['name' => 'Human']],
            ]),
        );

        self::assertResponseStatusCodeSame(409);
    }

    public function testImportReturns422WhenNoKind(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();

        $client->request(
            'POST',
            '/api/characters/import',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'gameId' => $game->getId(),
                'data' => ['token' => 'no-kind', 'name' => 'Kindless'],
            ]),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testImportReturns400WhenDataMissing(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/characters/import',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['gameId' => 1]),
        );

        self::assertResponseStatusCodeSame(400);
    }

    /**
     * Recursively removes every `id` key so two payloads can be compared modulo database ids.
     */
    private function stripIds(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $key => $item) {
            if ('id' === $key) {
                continue;
            }
            $out[$key] = $this->stripIds($item);
        }

        return $out;
    }
}
