<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322300000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add school, impactZone, duration, type to spell table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE spell ADD COLUMN school VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE spell ADD COLUMN impact_zone INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE spell ADD COLUMN duration INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE spell ADD COLUMN type VARCHAR(255) NOT NULL DEFAULT \'active\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE spell DROP COLUMN school');
        $this->addSql('ALTER TABLE spell DROP COLUMN impact_zone');
        $this->addSql('ALTER TABLE spell DROP COLUMN duration');
        $this->addSql('ALTER TABLE spell DROP COLUMN type');
    }
}
