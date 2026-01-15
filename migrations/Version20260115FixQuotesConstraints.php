<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Corriger les contraintes UNIQUE sur la table quotes
 * - Recréer UNIQUE sur quote_number (chaque devis a un numéro unique)
 * - S'assurer qu'il n'y a PAS de UNIQUE sur client_email (un client peut avoir plusieurs devis)
 */
final class Version20260115FixQuotesConstraints extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corriger les contraintes UNIQUE : quote_number doit être unique, client_email ne doit pas l\'être';
    }

    public function up(Schema $schema): void
    {
        // Supprimer toutes les anciennes contraintes UNIQUE (sauf PRIMARY)
        // Note: Cette commande échouera si l'index n'existe pas, c'est normal
        try {
            $this->addSql('DROP INDEX UNIQ_A1B588C5AC28B117 ON quotes');
        } catch (\Exception $e) {
            // Index déjà supprimé, on continue
        }
        
        // Recréer proprement l'index UNIQUE sur quote_number
        $this->addSql('CREATE UNIQUE INDEX UNIQ_QUOTE_NUMBER ON quotes (quote_number)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer l'index si on revient en arrière
        $this->addSql('DROP INDEX UNIQ_QUOTE_NUMBER ON quotes');
    }
}
