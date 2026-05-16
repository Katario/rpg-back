<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\CharacterController;
use App\Entity\Skill;
use App\Entity\Spell;
use App\Fixtures\DataFixtures\Factory\BeingTalentFactory;
use App\Fixtures\DataFixtures\Factory\CharacterClassFactory;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\SkillFactory;
use App\Fixtures\DataFixtures\Factory\SpellFactory;
use App\Fixtures\DataFixtures\Factory\TalentFactory;
use App\Fixtures\DataFixtures\Factory\WeaponFactory;
use App\ValueObject\DamageLine;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

        $data = json_decode((string) $client->getResponse()->getContent(), true);

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

        $data = json_decode((string) $client->getResponse()->getContent(), true);

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

        $data = json_decode((string) $client->getResponse()->getContent(), true);

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

    public function testShowExposesIsPassiveForSkillsAndSpells(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $game = GameFactory::createOne();

        $passiveSkill = (new Skill())
            ->setName('Meditation')
            ->setDescription('')
            ->setExhaustPointCost(0)
            ->setActionPointCost(0)
            ->setIsReady(true)
            ->setIsPrivate(false)
            ->setDamageLines([]);

        $activeSkill = (new Skill())
            ->setName('Slash')
            ->setDescription('')
            ->setExhaustPointCost(0)
            ->setActionPointCost(0)
            ->setIsReady(true)
            ->setIsPrivate(false)
            ->setDamageLines([new DamageLine(1, 6, 0)]);

        $passiveSpell = (new Spell())
            ->setName('Aura')
            ->setDescription('')
            ->setManaCost(0)
            ->setActionPointCost(0)
            ->setIsReady(true)
            ->setIsPrivate(false)
            ->setDamageLines([]);

        $activeSpell = (new Spell())
            ->setName('Fireball')
            ->setDescription('')
            ->setManaCost(0)
            ->setActionPointCost(0)
            ->setIsReady(true)
            ->setIsPrivate(false)
            ->setDamageLines([new DamageLine(3, 6, 0)]);

        $em->persist($passiveSkill);
        $em->persist($activeSkill);
        $em->persist($passiveSpell);
        $em->persist($activeSpell);

        $character = CharacterFactory::createOne([
            'game' => $game,
            'token' => 'is-passive-token',
            'skills' => [$passiveSkill, $activeSkill],
            'spells' => [$passiveSpell, $activeSpell],
        ]);

        $em->flush();

        $client->request('GET', '/api/characters/is-passive-token');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        $skillsByName = [];
        foreach ($data['skills'] as $skill) {
            $skillsByName[$skill['name']] = $skill;
        }
        self::assertTrue($skillsByName['Meditation']['isPassive']);
        self::assertFalse($skillsByName['Slash']['isPassive']);

        $spellsByName = [];
        foreach ($data['spells'] as $spell) {
            $spellsByName[$spell['name']] = $spell;
        }
        self::assertTrue($spellsByName['Aura']['isPassive']);
        self::assertFalse($spellsByName['Fireball']['isPassive']);
    }

    public function testShowReturns404WhenCharacterNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/characters/unknown-token');

        self::assertResponseStatusCodeSame(404);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
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

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Character not found', $data['error']);
    }

    /**
     * @var list<string>
     */
    private array $createdAvatarFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdAvatarFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->createdAvatarFiles = [];

        parent::tearDown();
    }

    private function createTempImage(string $mime = 'image/png'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'avatar_');
        $im = imagecreatetruecolor(8, 8);
        match ($mime) {
            'image/png' => imagepng($im, $tmp),
            'image/jpeg' => imagejpeg($im, $tmp),
            'image/webp' => imagewebp($im, $tmp),
            default => throw new \InvalidArgumentException('Unsupported mime'),
        };

        $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$mime];

        return new UploadedFile($tmp, 'avatar.'.$ext, $mime, null, true);
    }

    private function uploadDir(): string
    {
        return self::getContainer()->getParameter('kernel.project_dir').'/public/uploads/avatars';
    }

    public function testUploadAvatarSucceeds(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne([
            'game' => $game,
            'token' => 'avatar-token-1',
        ]);

        $file = $this->createTempImage();

        $client->request('POST', '/api/characters/avatar-token-1/avatar', files: ['avatar' => $file]);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertStringContainsString('/uploads/avatars/', $data['avatarUrl']);

        $relative = (string) parse_url($data['avatarUrl'], \PHP_URL_PATH);
        $this->createdAvatarFiles[] = $this->uploadDir().'/'.basename($relative);
        self::assertFileExists($this->createdAvatarFiles[0]);
    }

    public function testUploadAvatarFailsWithoutFile(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'no-file-token']);

        $client->request('POST', '/api/characters/no-file-token/avatar');

        self::assertResponseStatusCodeSame(400);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('No file provided', $data['error']);
    }

    public function testUploadAvatarFailsWithInvalidMimeType(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'bad-mime-token']);

        $tmp = tempnam(sys_get_temp_dir(), 'notimg_');
        file_put_contents($tmp, 'not an image');
        $file = new UploadedFile($tmp, 'avatar.txt', 'text/plain', null, true);

        $client->request('POST', '/api/characters/bad-mime-token/avatar', files: ['avatar' => $file]);

        self::assertResponseStatusCodeSame(400);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertStringContainsString('Invalid file type', $data['error']);
    }

    public function testUploadAvatarReturns404WhenCharacterNotFound(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/characters/unknown-token/avatar',
            files: ['avatar' => $this->createTempImage()],
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testUploadAvatarReplacesPreviousFile(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'replace-token']);

        $client->request(
            'POST',
            '/api/characters/replace-token/avatar',
            files: ['avatar' => $this->createTempImage()],
        );
        $first = json_decode((string) $client->getResponse()->getContent(), true);
        $firstPath = $this->uploadDir().'/'.basename((string) parse_url($first['avatarUrl'], \PHP_URL_PATH));
        $this->createdAvatarFiles[] = $firstPath;
        self::assertFileExists($firstPath);

        $client->request(
            'POST',
            '/api/characters/replace-token/avatar',
            files: ['avatar' => $this->createTempImage()],
        );
        $second = json_decode((string) $client->getResponse()->getContent(), true);
        $secondPath = $this->uploadDir().'/'.basename((string) parse_url($second['avatarUrl'], \PHP_URL_PATH));
        $this->createdAvatarFiles[] = $secondPath;

        self::assertFileDoesNotExist($firstPath);
        self::assertFileExists($secondPath);
    }

    public function testDeleteAvatarRemovesFileAndResetsUrl(): void
    {
        $client = static::createClient();

        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'delete-avatar-token']);

        $client->request(
            'POST',
            '/api/characters/delete-avatar-token/avatar',
            files: ['avatar' => $this->createTempImage()],
        );
        $uploaded = json_decode((string) $client->getResponse()->getContent(), true);
        $path = $this->uploadDir().'/'.basename((string) parse_url($uploaded['avatarUrl'], \PHP_URL_PATH));
        $this->createdAvatarFiles[] = $path;

        $client->request('DELETE', '/api/characters/delete-avatar-token/avatar');

        self::assertResponseStatusCodeSame(204);
        self::assertFileDoesNotExist($path);

        $client->request('GET', '/api/characters/delete-avatar-token');
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNull($data['avatarUrl']);
    }

    public function testDeleteAvatarReturns404WhenCharacterNotFound(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/characters/unknown-token/avatar');

        self::assertResponseStatusCodeSame(404);
    }
}
