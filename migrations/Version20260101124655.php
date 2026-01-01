<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260101124655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE remise (id INT AUTO_INCREMENT NOT NULL, compte_client_id INT NOT NULL, numero VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_117A95C7DA655713 (compte_client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE remise ADD CONSTRAINT FK_117A95C7DA655713 FOREIGN KEY (compte_client_id) REFERENCES compte_client (id)');
        $this->addSql('ALTER TABLE transaction ADD remise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D14E47A399 FOREIGN KEY (remise_id) REFERENCES remise (id)');
        $this->addSql('CREATE INDEX IDX_723705D14E47A399 ON transaction (remise_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D14E47A399');
        $this->addSql('ALTER TABLE remise DROP FOREIGN KEY FK_117A95C7DA655713');
        $this->addSql('DROP TABLE remise');
        $this->addSql('DROP INDEX IDX_723705D14E47A399 ON transaction');
        $this->addSql('ALTER TABLE transaction DROP remise_id');
    }
}
