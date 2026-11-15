<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261115212047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Every game has a game master, every being and equipment belongs to a game (NOT NULL).';
    }

    public function up(Schema $schema): void
    {
        // No data is rewritten: the migration stops, with nothing applied, if a row still has no game / game master
        // (production inventory of 2026-11-15: none).
        $orphans = (int) $this->connection->fetchOne(
            'SELECT (SELECT count(*) FROM being WHERE game_id IS NULL)
                  + (SELECT count(*) FROM equipment WHERE game_id IS NULL)
                  + (SELECT count(*) FROM game WHERE game_master IS NULL)'
        );
        $this->abortIf($orphans > 0, sprintf('%d row(s) without game / game master: assign them before migrating.', $orphans));

        $this->addSql('ALTER TABLE being ALTER game_id SET NOT NULL');
        $this->addSql('ALTER TABLE equipment ALTER game_id SET NOT NULL');
        $this->addSql('ALTER TABLE game ALTER game_master SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE being ALTER game_id DROP NOT NULL');
        $this->addSql('ALTER TABLE game ALTER game_master DROP NOT NULL');
        $this->addSql('ALTER TABLE equipment ALTER game_id DROP NOT NULL');
    }
}
