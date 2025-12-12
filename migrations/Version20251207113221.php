<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207113221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE instructor_application (id INT AUTO_INCREMENT NOT NULL, applicant_id INT NOT NULL, reviewed_by_id INT DEFAULT NULL, reason LONGTEXT NOT NULL, portfolio_filename VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', reviewed_at VARCHAR(255) DEFAULT NULL, INDEX IDX_44659C5597139001 (applicant_id), INDEX IDX_44659C55FC6B21F1 (reviewed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE instructor_application ADD CONSTRAINT FK_44659C5597139001 FOREIGN KEY (applicant_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE instructor_application ADD CONSTRAINT FK_44659C55FC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE instructor_application DROP FOREIGN KEY FK_44659C5597139001');
        $this->addSql('ALTER TABLE instructor_application DROP FOREIGN KEY FK_44659C55FC6B21F1');
        $this->addSql('DROP TABLE instructor_application');
    }
}
