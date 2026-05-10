<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506210600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create telegram_integrations table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE telegram_integrations (
  id BIGSERIAL NOT NULL,
  shop_id BIGINT NOT NULL,
  bot_token TEXT NOT NULL,
  chat_id TEXT NOT NULL,
  enabled BOOLEAN NOT NULL,
  created_at TIMESTAMPTZ NOT NULL,
  updated_at TIMESTAMPTZ NOT NULL,
  PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_telegram_integrations_shop_id ON telegram_integrations (shop_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE telegram_integrations');
    }
}

