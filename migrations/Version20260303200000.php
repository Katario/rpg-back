<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar_url to being (used by Character)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE being ADD avatar_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE being DROP avatar_url');
    }
}
