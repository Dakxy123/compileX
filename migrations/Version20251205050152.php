<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205050152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE module (id INT AUTO_INCREMENT NOT NULL, course_id INT NOT NULL, subject_id INT NOT NULL, instructor_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(100) NOT NULL, year_level INT NOT NULL, semester INT NOT NULL, schedule VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, INDEX IDX_C242628591CC992 (course_id), INDEX IDX_C24262823EDC87 (subject_id), INDEX IDX_C2426288C4FC193 (instructor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C242628591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C24262823EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C2426288C4FC193 FOREIGN KEY (instructor_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C242628591CC992');
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C24262823EDC87');
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C2426288C4FC193');
        $this->addSql('DROP TABLE module');
    }
}
