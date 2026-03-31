<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add kind_bonus table (STI) with KindTalentBonus';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE kind_bonus (id SERIAL NOT NULL, kind_id INT NOT NULL, talent_id INT DEFAULT NULL, value INT NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_KIND_BONUS_KIND ON kind_bonus (kind_id)');
        $this->addSql('CREATE INDEX IDX_KIND_BONUS_TALENT ON kind_bonus (talent_id)');
        $this->addSql('ALTER TABLE kind_bonus ADD CONSTRAINT FK_KIND_BONUS_KIND FOREIGN KEY (kind_id) REFERENCES kind (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE kind_bonus ADD CONSTRAINT FK_KIND_BONUS_TALENT FOREIGN KEY (talent_id) REFERENCES talent (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kind_bonus DROP CONSTRAINT FK_KIND_BONUS_KIND');
        $this->addSql('ALTER TABLE kind_bonus DROP CONSTRAINT FK_KIND_BONUS_TALENT');
        $this->addSql('DROP TABLE kind_bonus');
    }
}
