<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112073358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE section (id INT AUTO_INCREMENT NOT NULL, course_program_id INT DEFAULT NULL, name_id INT DEFAULT NULL, section_code VARCHAR(20) NOT NULL, year_level SMALLINT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, academic_year SMALLINT NOT NULL, UNIQUE INDEX UNIQ_2D737AEFF2ABDC93 (section_code), UNIQUE INDEX UNIQ_2D737AEF275AE721 (academic_year), INDEX IDX_2D737AEFE15A4989 (course_program_id), INDEX IDX_2D737AEF71179CD6 (name_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEFE15A4989 FOREIGN KEY (course_program_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF71179CD6 FOREIGN KEY (name_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE course ADD name VARCHAR(100) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_169E6FB977153098 ON course (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_169E6FB95E237E06 ON course (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBCE3E7A77153098 ON subject (code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEFE15A4989');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF71179CD6');
        $this->addSql('DROP TABLE section');
        $this->addSql('DROP INDEX UNIQ_169E6FB977153098 ON course');
        $this->addSql('DROP INDEX UNIQ_169E6FB95E237E06 ON course');
        $this->addSql('ALTER TABLE course DROP name');
        $this->addSql('DROP INDEX UNIQ_FBCE3E7A77153098 ON subject');
    }
}
