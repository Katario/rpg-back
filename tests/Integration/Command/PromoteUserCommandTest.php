<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Command\PromoteUserCommand;
use App\Entity\User;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(PromoteUserCommand::class)]
class PromoteUserCommandTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testGrantsTheAdminRole(): void
    {
        UserFactory::createOne(['email' => 'gm@example.com']);

        $tester = $this->commandTester();
        $tester->execute(['email' => 'GM@example.com']);

        $tester->assertCommandIsSuccessful();
        self::assertContains('ROLE_ADMIN', $this->user('gm@example.com')->getRoles());
    }

    public function testRevokesTheAdminRoleAndKeepsOtherRoles(): void
    {
        UserFactory::createOne(['email' => 'gm@example.com', 'roles' => ['ROLE_ADMIN', 'ROLE_BETA']]);

        $tester = $this->commandTester();
        $tester->execute(['email' => 'gm@example.com', '--revoke' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertEqualsCanonicalizing(['ROLE_BETA', 'ROLE_USER'], $this->user('gm@example.com')->getRoles());
    }

    public function testFailsForAnUnknownEmail(): void
    {
        $tester = $this->commandTester();

        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'nobody@example.com']));
    }

    private function user(string $email): User
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail($email);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function commandTester(): CommandTester
    {
        return new CommandTester(new Application(self::bootKernel())->find('app:user:promote'));
    }
}
