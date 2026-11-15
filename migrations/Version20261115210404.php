<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261115210404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game memberships (players), invite codes and archiving; existing character owners join their game.';
    }

    public function up(Schema $schema): void
    {
        // Additive only: a new table and two nullable columns. Existing games start closed (no invite code).
        $this->addSql('CREATE TABLE game_membership (id SERIAL NOT NULL, game_id INT NOT NULL, user_id INT NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_68231568E48FD905 ON game_membership (game_id)');
        $this->addSql('CREATE INDEX IDX_68231568A76ED395 ON game_membership (user_id)');
        $this->addSql('CREATE UNIQUE INDEX game_membership_game_user ON game_membership (game_id, user_id)');
        $this->addSql('COMMENT ON COLUMN game_membership.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE game_membership ADD CONSTRAINT FK_68231568E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_membership ADD CONSTRAINT FK_68231568A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD invite_code VARCHAR(8) DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD archived_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN game.archived_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318C6F21F112 ON game (invite_code)');

        $this->addSql(<<<'SQL'
            INSERT INTO game_membership (game_id, user_id, joined_at)
            SELECT DISTINCT b.game_id, b.user_id, LOCALTIMESTAMP(0)
            FROM being b
            JOIN game g ON g.id = b.game_id
            WHERE b.type = 'character'
              AND b.user_id IS NOT NULL
              AND b.user_id IS DISTINCT FROM g.game_master
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_membership DROP CONSTRAINT FK_68231568E48FD905');
        $this->addSql('ALTER TABLE game_membership DROP CONSTRAINT FK_68231568A76ED395');
        $this->addSql('DROP TABLE game_membership');
        $this->addSql('DROP INDEX UNIQ_232B318C6F21F112');
        $this->addSql('ALTER TABLE game DROP invite_code');
        $this->addSql('ALTER TABLE game DROP archived_at');
    }
}
