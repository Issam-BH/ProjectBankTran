<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117154756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE po_request_account (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', password VARCHAR(255) NOT NULL, action VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE compte_client ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE compte_client ADD CONSTRAINT FK_1DDD5D62A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1DDD5D62A76ED395 ON compte_client (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE po_request_account');
        $this->addSql('ALTER TABLE compte_client DROP FOREIGN KEY FK_1DDD5D62A76ED395');
        $this->addSql('DROP INDEX UNIQ_1DDD5D62A76ED395 ON compte_client');
        $this->addSql('ALTER TABLE compte_client DROP user_id');
    }
}
