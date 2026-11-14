<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Command\ResetUserPasswordCommand;
use App\Entity\User;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use App\Tests\Support\IssuesApiTokens;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(ResetUserPasswordCommand::class)]
#[CoversClass(ApiTokenRepository::class)]
class ResetUserPasswordCommandTest extends KernelTestCase
{
    use Factories;
    use IssuesApiTokens;
    use ResetDatabase;

    public function testSetsTheNewPasswordAndRevokesApiTokens(): void
    {
        $user = UserFactory::createOne(['email' => 'gm@example.com', 'password' => 'old-password'])->_real();
        $this->issueApiToken($user);
        $this->issueApiToken(UserFactory::createOne()->_real());

        $tester = $this->commandTester();
        $tester->setInputs(['new-password', 'new-password']);
        $tester->execute(['email' => 'gm@example.com']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('1 API token(s) revoked', $tester->getDisplay());
        self::assertTrue($this->hasher()->isPasswordValid($this->user('gm@example.com'), 'new-password'));
        // Only the user's tokens are revoked.
        self::assertCount(1, self::getContainer()->get(ApiTokenRepository::class)->findAll());
    }

    public function testRefusesATooShortPassword(): void
    {
        UserFactory::createOne(['email' => 'gm@example.com', 'password' => 'old-password']);

        $tester = $this->commandTester();
        $tester->setInputs(['short']);

        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'gm@example.com']));
        self::assertTrue($this->hasher()->isPasswordValid($this->user('gm@example.com'), 'old-password'));
    }

    public function testRefusesMismatchingPasswords(): void
    {
        UserFactory::createOne(['email' => 'gm@example.com', 'password' => 'old-password']);

        $tester = $this->commandTester();
        $tester->setInputs(['new-password', 'other-password']);

        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'gm@example.com']));
        self::assertTrue($this->hasher()->isPasswordValid($this->user('gm@example.com'), 'old-password'));
    }

    public function testFailsForAnUnknownEmail(): void
    {
        self::assertSame(Command::FAILURE, $this->commandTester()->execute(['email' => 'nobody@example.com']));
    }

    private function user(string $email): User
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail($email);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function hasher(): UserPasswordHasherInterface
    {
        return self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    private function commandTester(): CommandTester
    {
        return new CommandTester(new Application(self::bootKernel())->find('app:user:reset-password'));
    }
}
