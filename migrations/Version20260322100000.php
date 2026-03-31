<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add weight column to equipment table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipment ADD COLUMN weight INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipment DROP COLUMN weight');
    }
}
