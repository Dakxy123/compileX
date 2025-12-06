<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206082322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE enrollment (id INT AUTO_INCREMENT NOT NULL, student_profile_id INT NOT NULL, subject_id INT NOT NULL, status VARCHAR(20) NOT NULL, enrolled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DBDCD7E12125FF59 (student_profile_id), INDEX IDX_DBDCD7E123EDC87 (subject_id), UNIQUE INDEX uniq_enrollment_student_subject (student_profile_id, subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E12125FF59 FOREIGN KEY (student_profile_id) REFERENCES student_profile (id)');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E123EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E12125FF59');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E123EDC87');
        $this->addSql('DROP TABLE enrollment');
    }
}
