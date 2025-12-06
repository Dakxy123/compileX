<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206180802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE instructor_assignment (id INT AUTO_INCREMENT NOT NULL, instructor_id INT NOT NULL, subject_id INT NOT NULL, is_primary TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DB6DCEB08C4FC193 (instructor_id), INDEX IDX_DB6DCEB023EDC87 (subject_id), UNIQUE INDEX uniq_instructor_subject (instructor_id, subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE instructor_assignment ADD CONSTRAINT FK_DB6DCEB08C4FC193 FOREIGN KEY (instructor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE instructor_assignment ADD CONSTRAINT FK_DB6DCEB023EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE instructor_assignment DROP FOREIGN KEY FK_DB6DCEB08C4FC193');
        $this->addSql('ALTER TABLE instructor_assignment DROP FOREIGN KEY FK_DB6DCEB023EDC87');
        $this->addSql('DROP TABLE instructor_assignment');
    }
}
