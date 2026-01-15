<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109103949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE quote_items (id INT AUTO_INCREMENT NOT NULL, quote_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(10, 2) NOT NULL, unit_price_ht NUMERIC(10, 2) NOT NULL, INDEX IDX_ECE1642CDB805178 (quote_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quotes (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, quote_number VARCHAR(50) DEFAULT NULL, company_name VARCHAR(255) NOT NULL, company_contact VARCHAR(255) NOT NULL, company_address LONGTEXT NOT NULL, company_email VARCHAR(255) NOT NULL, company_phone VARCHAR(50) DEFAULT NULL, company_siret VARCHAR(50) DEFAULT NULL, company_logo VARCHAR(255) DEFAULT NULL, client_name VARCHAR(255) NOT NULL, client_company VARCHAR(255) DEFAULT NULL, client_address LONGTEXT NOT NULL, client_email VARCHAR(255) DEFAULT NULL, quote_date DATETIME NOT NULL, quote_valid_until DATETIME DEFAULT NULL, quote_description LONGTEXT DEFAULT NULL, vat_rate NUMERIC(5, 2) NOT NULL, payment_terms LONGTEXT DEFAULT NULL, pdf_template VARCHAR(20) DEFAULT NULL, total_ht NUMERIC(10, 2) DEFAULT NULL, total_ttc NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_A1B588C5AC28B117 (quote_number), INDEX IDX_A1B588C5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `users` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, subscription VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quote_items ADD CONSTRAINT FK_ECE1642CDB805178 FOREIGN KEY (quote_id) REFERENCES quotes (id)');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_A1B588C5A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote_items DROP FOREIGN KEY FK_ECE1642CDB805178');
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_A1B588C5A76ED395');
        $this->addSql('DROP TABLE quote_items');
        $this->addSql('DROP TABLE quotes');
        $this->addSql('DROP TABLE `users`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
