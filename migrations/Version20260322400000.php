<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322400000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add damage columns to weapon (equipment STI), replace dice_value with damage on skill and spell';
    }

    public function up(Schema $schema): void
    {
        // Weapon damage columns (nullable — armor rows have no damage)
        $this->addSql("ALTER TABLE equipment ADD COLUMN damage_dice JSON DEFAULT '[]'");
        $this->addSql('ALTER TABLE equipment ADD COLUMN damage_bonus INT DEFAULT 0');

        // Skill: replace dice_value with damage columns
        $this->addSql('ALTER TABLE skill DROP COLUMN dice_value');
        $this->addSql("ALTER TABLE skill ADD COLUMN damage_dice JSON NOT NULL DEFAULT '[]'");
        $this->addSql('ALTER TABLE skill ADD COLUMN damage_bonus INT NOT NULL DEFAULT 0');

        // Spell: replace dice_value with damage columns
        $this->addSql('ALTER TABLE spell DROP COLUMN dice_value');
        $this->addSql("ALTER TABLE spell ADD COLUMN damage_dice JSON NOT NULL DEFAULT '[]'");
        $this->addSql('ALTER TABLE spell ADD COLUMN damage_bonus INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipment DROP COLUMN damage_dice');
        $this->addSql('ALTER TABLE equipment DROP COLUMN damage_bonus');

        $this->addSql('ALTER TABLE skill DROP COLUMN damage_dice');
        $this->addSql('ALTER TABLE skill DROP COLUMN damage_bonus');
        $this->addSql("ALTER TABLE skill ADD COLUMN dice_value VARCHAR(255) NOT NULL DEFAULT ''");

        $this->addSql('ALTER TABLE spell DROP COLUMN damage_dice');
        $this->addSql('ALTER TABLE spell DROP COLUMN damage_bonus');
        $this->addSql("ALTER TABLE spell ADD COLUMN dice_value VARCHAR(255) NOT NULL DEFAULT ''");
    }
}
