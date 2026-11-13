<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the api_token table (API access tokens, hashed).';
    }

    public function up(Schema $schema): void
    {
        // New table only: no existing data is touched.
        $this->addSql('CREATE TABLE api_token (id SERIAL NOT NULL, user_id INT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7BA2F5EBB3BC57DA ON api_token (token_hash)');
        $this->addSql('CREATE INDEX IDX_7BA2F5EBA76ED395 ON api_token (user_id)');
        $this->addSql('ALTER TABLE api_token ADD CONSTRAINT FK_7BA2F5EBA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN api_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN api_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN api_token.last_used_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_token DROP CONSTRAINT FK_7BA2F5EBA76ED395');
        $this->addSql('DROP TABLE api_token');
    }
}
