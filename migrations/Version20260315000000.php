<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260315000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace being_items ManyToMany with being_item join entity (adds quantity)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE being_items');
        $this->addSql('CREATE TABLE being_item (being_id INT NOT NULL, item_id INT NOT NULL, quantity INT NOT NULL DEFAULT 1, PRIMARY KEY (being_id, item_id))');
        $this->addSql('ALTER TABLE being_item ADD CONSTRAINT FK_being_item_being FOREIGN KEY (being_id) REFERENCES being (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE being_item ADD CONSTRAINT FK_being_item_item FOREIGN KEY (item_id) REFERENCES item (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE being_item');
        $this->addSql('CREATE TABLE being_items (being_id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY (being_id, item_id))');
        $this->addSql('ALTER TABLE being_items ADD CONSTRAINT FK_being_items_being FOREIGN KEY (being_id) REFERENCES being (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE being_items ADD CONSTRAINT FK_being_items_item FOREIGN KEY (item_id) REFERENCES item (id)');
    }
}
