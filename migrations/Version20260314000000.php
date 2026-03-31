<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add range to spell';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE spell ADD range INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE spell DROP range');
    }
}
