<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set orders.total precision for DBAL cross-platform schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ALTER COLUMN total TYPE NUMERIC(12, 2)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ALTER COLUMN total TYPE NUMERIC');
    }
}
