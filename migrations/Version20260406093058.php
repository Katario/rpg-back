<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406093058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipment DROP damage_bonus');
        $this->addSql('ALTER TABLE equipment RENAME COLUMN damage_dice TO damage_lines');
        $this->addSql('ALTER TABLE skill DROP damage_bonus');
        $this->addSql('ALTER TABLE skill RENAME COLUMN damage_dice TO damage_lines');
        $this->addSql('ALTER TABLE spell DROP damage_bonus');
        $this->addSql('ALTER TABLE spell RENAME COLUMN damage_dice TO damage_lines');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE spell ADD damage_bonus INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE spell RENAME COLUMN damage_lines TO damage_dice');
        $this->addSql('ALTER TABLE equipment ADD damage_bonus INT DEFAULT 0');
        $this->addSql('ALTER TABLE equipment RENAME COLUMN damage_lines TO damage_dice');
        $this->addSql('ALTER TABLE skill ADD damage_bonus INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE skill RENAME COLUMN damage_lines TO damage_dice');
    }
}
