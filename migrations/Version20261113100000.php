<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Users sign in with their email: username becomes optional (existing values are kept).';
    }

    public function up(Schema $schema): void
    {
        // Only relaxes a constraint: no data is changed or removed. The unique index stays
        // (PostgreSQL allows several NULLs in a unique index).
        $this->addSql('ALTER TABLE "user" ALTER username DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Fails if accounts without username were created meanwhile: give them one first.
        $this->addSql('ALTER TABLE "user" ALTER username SET NOT NULL');
    }
}
