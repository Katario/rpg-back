<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Web;

use App\Controller\UserController;
use App\Entity\User;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(UserController::class)]
#[CoversClass(UserRepository::class)]
class UserControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

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

    private function signIn(KernelBrowser $client, string $identifier, string $password): void
    {
        $client->request('POST', '/sign-in', ['_username' => $identifier, '_password' => $password]);
    }
}
