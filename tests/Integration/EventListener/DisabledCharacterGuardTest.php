<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\Character;
use App\EventListener\DisabledCharacterGuard;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\CharacterRepository;
use App\Serializer\CharacterSerializer;
use App\Tests\Support\IssuesApiTokens;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(DisabledCharacterGuard::class)]
#[CoversClass(Character::class)]
#[CoversClass(CharacterSerializer::class)]
class DisabledCharacterGuardTest extends WebTestCase
{
    use Factories;
    use IssuesApiTokens;
    use ResetDatabase;

    public function testADisabledCharacterStaysReadable(): void
    {
        $client = static::createClient();
        $this->character(disabled: true);

        $client->request('GET', '/api/characters/sheet-token');
        self::assertResponseIsSuccessful();
        self::assertTrue($this->json($client)['disabled']);

        $client->request('GET', '/api/characters/sheet-token/export');
        self::assertResponseIsSuccessful();
    }

    public function testEveryChangeToADisabledCharacterIsRefused(): void
    {
        $client = static::createClient();
        $this->character(disabled: true);

        $this->patchStats($client, 'sheet-token');
        self::assertResponseStatusCodeSame(403);
        self::assertSame(['error' => 'This character is disabled.'], $this->json($client));

        $client->request('POST', '/api/characters/sheet-token/level-up');
        self::assertResponseStatusCodeSame(403);

        $client->request('POST', '/api/characters/sheet-token/weapons', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');
        self::assertResponseStatusCodeSame(403);

        $client->request('DELETE', '/api/characters/sheet-token');
        self::assertResponseStatusCodeSame(403);

        $character = self::getContainer()->get(CharacterRepository::class)->findOneByToken('sheet-token');
        self::assertInstanceOf(Character::class, $character);
        self::assertSame(80, $character->getCurrentHealthPoints());
    }

    public function testTheOwnerIsRefusedToo(): void
    {
        $client = static::createClient();
        $owner = UserFactory::createOne()->_real();
        $this->character(disabled: true, owner: $owner);

        $this->patchStats($client, 'sheet-token', $this->bearer($this->issueApiToken($owner)));

        self::assertResponseStatusCodeSame(403);
    }

    public function testAnEnabledCharacterCanBeChanged(): void
    {
        $client = static::createClient();
        $this->character(disabled: false);

        $this->patchStats($client, 'sheet-token');

        self::assertResponseIsSuccessful();
    }

    public function testOwnedCharactersListShowsTheFlag(): void
    {
        $client = static::createClient();
        $owner = UserFactory::createOne()->_real();
        $this->character(disabled: true, owner: $owner);

        $client->request('GET', '/api/me/characters', server: $this->bearer($this->issueApiToken($owner)));

        self::assertResponseIsSuccessful();
        self::assertTrue($this->json($client)[0]['disabled']);
    }

    public function testEnableAndDisable(): void
    {
        $character = new Character();

        self::assertFalse($character->isDisabled());
        self::assertTrue($character->disable()->isDisabled());
        self::assertFalse($character->enable()->isDisabled());
    }

    private function character(bool $disabled, mixed $owner = null): void
    {
        $character = CharacterFactory::createOne([
            'token' => 'sheet-token',
            'kind' => KindFactory::createOne(),
            'user' => $owner,
            'currentHealthPoints' => 80,
            'maxHealthPoints' => 100,
        ])->_real();

        if ($disabled) {
            $character->disable();
            self::getContainer()->get(EntityManagerInterface::class)->flush();
        }
    }

    /**
     * @param array<string, string> $server
     */
    private function patchStats(KernelBrowser $client, string $token, array $server = []): void
    {
        $client->request(
            'PATCH',
            "/api/characters/$token/stats",
            server: ['CONTENT_TYPE' => 'application/json'] + $server,
            content: (string) json_encode(['currentHealthPoints' => 50]),
        );
    }

    /**
     * @return array<mixed>
     */
    private function json(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true);
    }
}
