<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506211600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create shops table and seed initial data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE shops (
  id BIGSERIAL NOT NULL,
  name TEXT NOT NULL,
  PRIMARY KEY(id)
)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO shops (id, name) VALUES
  (1, 'Shop #1'),
  (2, 'Shop #2'),
  (3, 'Shop #3')
SQL);

        // Ensure sequence is ahead of seeded ids
        $this->addSql("SELECT setval(pg_get_serial_sequence('shops', 'id'), (SELECT MAX(id) FROM shops))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shops');
    }
}

