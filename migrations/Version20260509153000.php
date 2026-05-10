<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create telegram_send_log table for order notification audit';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE telegram_send_log (
  id BIGSERIAL NOT NULL,
  shop_id TEXT NOT NULL,
  order_id BIGINT NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(16) NOT NULL CHECK (status IN ('SENT','FAILED')),
  error TEXT,
  sent_at TIMESTAMPTZ NOT NULL,
  PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_telegram_send_log_shop_order ON telegram_send_log (shop_id, order_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE telegram_send_log');
    }
}
