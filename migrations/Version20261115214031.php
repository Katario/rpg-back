<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261115214031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add being.is_disabled (disabled characters are read-only on the API).';
    }

    public function up(Schema $schema): void
    {
        // Additive: existing rows get the default (false), no data is rewritten. Nullable because single table
        // inheritance columns of a subclass (Character) always are; Doctrine always writes a boolean.
        $this->addSql('ALTER TABLE being ADD is_disabled BOOLEAN DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE being DROP is_disabled');
    }
}
