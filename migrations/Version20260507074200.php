<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507074200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create orders table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE orders (
  id BIGSERIAL NOT NULL,
  shop_id TEXT NOT NULL,
  number TEXT NOT NULL,
  total NUMERIC NOT NULL,
  customer_name TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL,
  PRIMARY KEY(id)
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE orders');
    }
}

