<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove current_load_points from being table (now computed from inventory)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE being DROP COLUMN current_load_points');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE being ADD COLUMN current_load_points INT NOT NULL DEFAULT 0');
    }
}
