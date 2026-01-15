<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajouter les champs pack_credits et pack_expires_at
 */
final class Version20260115AddPackFields extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs pack_credits et pack_expires_at à la table users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD pack_credits INT DEFAULT 0');
        $this->addSql('ALTER TABLE users ADD pack_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP pack_credits');
        $this->addSql('ALTER TABLE users DROP pack_expires_at');
    }
}
