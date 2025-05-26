<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250522162947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD user_id INT NOT NULL, ADD activity_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD CONSTRAINT FK_F529939881C06096 FOREIGN KEY (activity_id) REFERENCES activity (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F5299398A76ED395 ON `order` (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F529939881C06096 ON `order` (activity_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP FOREIGN KEY FK_F529939881C06096
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F5299398A76ED395 ON `order`
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F529939881C06096 ON `order`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP user_id, DROP activity_id
        SQL);
    }
}
