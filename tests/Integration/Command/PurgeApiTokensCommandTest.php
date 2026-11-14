<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Command\PurgeApiTokensCommand;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\ApiTokenRepository;
use App\Tests\Support\IssuesApiTokens;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(PurgeApiTokensCommand::class)]
#[CoversClass(ApiTokenRepository::class)]
class PurgeApiTokensCommandTest extends KernelTestCase
{
    use Factories;
    use IssuesApiTokens;
    use ResetDatabase;

    public function testDeletesOnlyExpiredTokens(): void
    {
        $user = UserFactory::createOne()->_real();
        $this->issueApiToken($user, new \DateTimeImmutable('-31 days'));
        $valid = $this->issueApiToken($user);

        $tester = new CommandTester(new Application(self::bootKernel())->find('app:api-token:purge'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('1 expired API token(s) deleted.', $tester->getDisplay());

        $repository = self::getContainer()->get(ApiTokenRepository::class);
        self::assertCount(1, $repository->findAll());
        self::assertNotNull($repository->findOneByPlainToken($valid));
    }
}
