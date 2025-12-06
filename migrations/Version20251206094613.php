<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206094613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_enrollment_student_subject ON enrollment');
        $this->addSql('ALTER TABLE enrollment ADD score DOUBLE PRECISION DEFAULT NULL, ADD grade VARCHAR(10) DEFAULT NULL, ADD remarks LONGTEXT DEFAULT NULL, DROP enrolled_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment ADD enrolled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP score, DROP grade, DROP remarks');
        $this->addSql('CREATE UNIQUE INDEX uniq_enrollment_student_subject ON enrollment (student_profile_id, subject_id)');
    }
}
