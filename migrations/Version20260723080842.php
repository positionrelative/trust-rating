<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723080842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the review table with validation constraints and query indexes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE review (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                company_name VARCHAR(255) NOT NULL,
                rating INTEGER NOT NULL,
                review_text CLOB NOT NULL,
                author_email VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
            )
            SQL);

        $this->addSql(
            'CREATE INDEX idx_review_company_name ON review (company_name)'
        );

        $this->addSql(
            'CREATE INDEX idx_review_created_at ON review (created_at)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE review');
    }
}
