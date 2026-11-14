<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Web;

use App\Controller\UserController;
use App\Dto\RegisterUserRequest;
use App\Entity\User;
use App\Factory\UserFactory as AppUserFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\FormType\UserType;
use App\Repository\UserRepository;
use App\Tests\Support\ResetsRateLimiters;
use App\Validator\UniqueUserEmailValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(UserController::class)]
#[CoversClass(UserRepository::class)]
#[CoversClass(UserType::class)]
#[CoversClass(RegisterUserRequest::class)]
#[CoversClass(AppUserFactory::class)]
#[CoversClass(UniqueUserEmailValidator::class)]
class UserControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use ResetsRateLimiters;

    public function testSignUpWithoutUsernameCreatesAccount(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/sign-up');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'user[email]' => 'New.Player@Example.com',
            'user[password]' => 'secret-password',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/sign-in');

        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'new.player@example.com']);
        self::assertInstanceOf(User::class, $user);
        self::assertNull($user->getUsername());
    }

    public function testSignUpRejectsAnAlreadyUsedEmailCaseInsensitively(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'aldric@example.com']);

        $this->submitSignUp($client, 'Aldric@Example.com', 'secret-password');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'This email is already used.');
        self::assertCount(1, self::getContainer()->get(UserRepository::class)->findAll());
    }

    public function testSignUpRejectsAShortPassword(): void
    {
        $client = static::createClient();

        $this->submitSignUp($client, 'new.player@example.com', 'short');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('#user_email[value="new.player@example.com"]');
        self::assertCount(0, self::getContainer()->get(UserRepository::class)->findAll());
    }

    public function testSignUpIsRateLimitedPerIp(): void
    {
        $client = static::createClient();

        for ($i = 0; $i < 10; ++$i) {
            $this->submitSignUp($client, "player$i@example.com", 'short');
            self::assertResponseStatusCodeSame(422);
        }

        $this->submitSignUp($client, 'one-too-many@example.com', 'secret-password');

        self::assertResponseStatusCodeSame(429);
        self::assertSelectorTextContains('form', 'Too many registration attempts');
        self::assertCount(0, self::getContainer()->get(UserRepository::class)->findAll());
    }

    public function testSignInIsThrottledAfterFiveFailedAttempts(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'aldric@example.com', 'password' => 'secret-password']);

        for ($i = 0; $i < 5; ++$i) {
            $this->signIn($client, 'aldric@example.com', 'wrong-password');
        }
        $this->signIn($client, 'aldric@example.com', 'secret-password');

        $client->request('GET', '/');
        self::assertResponseRedirects('http://localhost/sign-in');
    }

    public function testSignInWithEmail(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'aldric@example.com', 'password' => 'secret-password']);

        $this->signIn($client, 'aldric@example.com', 'secret-password');

        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testSignInWithUsernameIsRejected(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'aldric@example.com', 'username' => 'aldric', 'password' => 'secret-password']);

        $this->signIn($client, 'aldric', 'secret-password');

        $client->request('GET', '/');
        self::assertResponseRedirects('http://localhost/sign-in');
    }

    public function testLegacyMixedCaseEmailCanStillSignIn(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => 'legacy@example.com', 'password' => 'secret-password']);

        // Simulate an account created before emails were normalized, then drop the stale
        // in-memory copy so the user is reloaded from the database as in production.
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()
            ->executeStatement('UPDATE "user" SET email = ? WHERE id = ?', ['Legacy@Example.COM', $user->getId()]);
        $em->clear();

        $this->signIn($client, 'legacy@example.com', 'secret-password');

        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    private function submitSignUp(KernelBrowser $client, string $email, string $password): void
    {
        $crawler = $client->request('GET', '/sign-up');
        $client->submit($crawler->filter('form')->form([
            'user[email]' => $email,
            'user[password]' => $password,
        ]));
    }

    private function signIn(KernelBrowser $client, string $identifier, string $password): void
    {
        $client->request('POST', '/sign-in', ['_username' => $identifier, '_password' => $password]);
    }
}
