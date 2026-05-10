<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507210500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make orders.number unique';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_orders_number ON orders (number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_orders_number');
    }
}

