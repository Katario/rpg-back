<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add primary and secondary talent affinity tables for Being';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE being_primary_talents (being_id INT NOT NULL, talent_id INT NOT NULL, PRIMARY KEY (being_id, talent_id))');
        $this->addSql('CREATE INDEX IDX_being_primary_talents_being ON being_primary_talents (being_id)');
        $this->addSql('CREATE INDEX IDX_being_primary_talents_talent ON being_primary_talents (talent_id)');
        $this->addSql('ALTER TABLE being_primary_talents ADD CONSTRAINT FK_being_primary_talents_being FOREIGN KEY (being_id) REFERENCES being (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE being_primary_talents ADD CONSTRAINT FK_being_primary_talents_talent FOREIGN KEY (talent_id) REFERENCES talent (id)');

        $this->addSql('CREATE TABLE being_secondary_talents (being_id INT NOT NULL, talent_id INT NOT NULL, PRIMARY KEY (being_id, talent_id))');
        $this->addSql('CREATE INDEX IDX_being_secondary_talents_being ON being_secondary_talents (being_id)');
        $this->addSql('CREATE INDEX IDX_being_secondary_talents_talent ON being_secondary_talents (talent_id)');
        $this->addSql('ALTER TABLE being_secondary_talents ADD CONSTRAINT FK_being_secondary_talents_being FOREIGN KEY (being_id) REFERENCES being (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE being_secondary_talents ADD CONSTRAINT FK_being_secondary_talents_talent FOREIGN KEY (talent_id) REFERENCES talent (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE being_primary_talents');
        $this->addSql('DROP TABLE being_secondary_talents');
    }
}
