<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_equipped to equipment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipment ADD is_equipped BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipment DROP is_equipped');
    }
}
