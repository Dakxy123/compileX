<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113030754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE course_offering (id INT AUTO_INCREMENT NOT NULL, section_id INT NOT NULL, instructor_id INT DEFAULT NULL, subject_id INT NOT NULL, term VARCHAR(20) DEFAULT NULL, capacity SMALLINT DEFAULT NULL, status VARCHAR(20) NOT NULL, academic_year VARCHAR(9) NOT NULL, schedule VARCHAR(100) DEFAULT NULL, INDEX IDX_CDC47E7D823E37A (section_id), INDEX IDX_CDC47E78C4FC193 (instructor_id), INDEX IDX_CDC47E723EDC87 (subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE enrollment (id INT AUTO_INCREMENT NOT NULL, offering_id INT NOT NULL, enrolled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(255) NOT NULL, INDEX IDX_DBDCD7E18EDF74F0 (offering_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student (id INT AUTO_INCREMENT NOT NULL, student_id INT NOT NULL, section_id INT DEFAULT NULL, fname VARCHAR(100) NOT NULL, mname VARCHAR(50) DEFAULT NULL, lname VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_B723AF33E7927C74 (email), INDEX IDX_B723AF33D823E37A (section_id), UNIQUE INDEX UNIQ_B723AF33CB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE course_offering ADD CONSTRAINT FK_CDC47E7D823E37A FOREIGN KEY (section_id) REFERENCES section (id)');
        $this->addSql('ALTER TABLE course_offering ADD CONSTRAINT FK_CDC47E78C4FC193 FOREIGN KEY (instructor_id) REFERENCES instructors (id)');
        $this->addSql('ALTER TABLE course_offering ADD CONSTRAINT FK_CDC47E723EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E18EDF74F0 FOREIGN KEY (offering_id) REFERENCES course_offering (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33D823E37A FOREIGN KEY (section_id) REFERENCES section (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE subject ADD units SMALLINT DEFAULT NULL, CHANGE name title VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_offering DROP FOREIGN KEY FK_CDC47E7D823E37A');
        $this->addSql('ALTER TABLE course_offering DROP FOREIGN KEY FK_CDC47E78C4FC193');
        $this->addSql('ALTER TABLE course_offering DROP FOREIGN KEY FK_CDC47E723EDC87');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E18EDF74F0');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33D823E37A');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33CB944F1A');
        $this->addSql('DROP TABLE course_offering');
        $this->addSql('DROP TABLE enrollment');
        $this->addSql('DROP TABLE student');
        $this->addSql('ALTER TABLE subject DROP units, CHANGE title name VARCHAR(100) NOT NULL');
    }
}
