<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add load points and mental points to being';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE being ADD current_load_points INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE being ADD max_load_points INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE being ADD current_mental_points INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE being ADD max_mental_points INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE being ALTER current_load_points DROP DEFAULT');
        $this->addSql('ALTER TABLE being ALTER max_load_points DROP DEFAULT');
        $this->addSql('ALTER TABLE being ALTER current_mental_points DROP DEFAULT');
        $this->addSql('ALTER TABLE being ALTER max_mental_points DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE being DROP current_load_points');
        $this->addSql('ALTER TABLE being DROP max_load_points');
        $this->addSql('ALTER TABLE being DROP current_mental_points');
        $this->addSql('ALTER TABLE being DROP max_mental_points');
    }
}
