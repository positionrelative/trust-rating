<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723084816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE company (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_company_name ON company (name)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT id, rating, review_text, author_email, created_at, updated_at FROM review');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating INTEGER NOT NULL, review_text CLOB NOT NULL, author_email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, company_id INTEGER NOT NULL, CONSTRAINT FK_794381C6979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review (id, rating, review_text, author_email, created_at, updated_at) SELECT id, rating, review_text, author_email, created_at, updated_at FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
        $this->addSql('CREATE INDEX IDX_794381C6979B1AD6 ON review (company_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE company');
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT id, rating, review_text, author_email, created_at, updated_at FROM review');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating INTEGER NOT NULL, review_text CLOB NOT NULL, author_email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, company_name VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO review (id, rating, review_text, author_email, created_at, updated_at) SELECT id, rating, review_text, author_email, created_at, updated_at FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
    }
}
