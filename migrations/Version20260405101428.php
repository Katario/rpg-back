<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405101428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_being_primary_talents_being RENAME TO IDX_560F82B7186A5FCB');
        $this->addSql('ALTER INDEX idx_being_primary_talents_talent RENAME TO IDX_560F82B718777CEF');
        $this->addSql('ALTER INDEX idx_being_secondary_talents_being RENAME TO IDX_E76173FA186A5FCB');
        $this->addSql('ALTER INDEX idx_being_secondary_talents_talent RENAME TO IDX_E76173FA18777CEF');
        $this->addSql('ALTER TABLE being_item DROP CONSTRAINT fk_being_item_being');
        $this->addSql('ALTER TABLE being_item ADD CONSTRAINT FK_A5AC6902186A5FCB FOREIGN KEY (being_id) REFERENCES being (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE kind_bonus ALTER talent_id SET NOT NULL');
        $this->addSql('ALTER INDEX idx_kind_bonus_kind RENAME TO IDX_24B7483430602CA9');
        $this->addSql('ALTER INDEX idx_kind_bonus_talent RENAME TO IDX_24B7483418777CEF');
        $this->addSql('ALTER TABLE talent_level ADD tier VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE talent_level RENAME COLUMN level TO required_points');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE kind_bonus ALTER talent_id DROP NOT NULL');
        $this->addSql('ALTER INDEX idx_24b7483430602ca9 RENAME TO idx_kind_bonus_kind');
        $this->addSql('ALTER INDEX idx_24b7483418777cef RENAME TO idx_kind_bonus_talent');
        $this->addSql('ALTER TABLE being_item DROP CONSTRAINT FK_A5AC6902186A5FCB');
        $this->addSql('ALTER TABLE being_item ADD CONSTRAINT fk_being_item_being FOREIGN KEY (being_id) REFERENCES being (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE talent_level DROP tier');
        $this->addSql('ALTER TABLE talent_level RENAME COLUMN required_points TO level');
        $this->addSql('ALTER INDEX idx_e76173fa186a5fcb RENAME TO idx_being_secondary_talents_being');
        $this->addSql('ALTER INDEX idx_e76173fa18777cef RENAME TO idx_being_secondary_talents_talent');
        $this->addSql('ALTER INDEX idx_560f82b7186a5fcb RENAME TO idx_being_primary_talents_being');
        $this->addSql('ALTER INDEX idx_560f82b718777cef RENAME TO idx_being_primary_talents_talent');
    }
}
