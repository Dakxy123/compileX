<?php

namespace App\Entity;

use App\Repository\InstructorAssignmentRepository;
use App\Entity\User;
use App\Entity\Subject;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InstructorAssignmentRepository::class)]
#[ORM\Table(name: 'instructor_assignment')]
#[ORM\UniqueConstraint(name: 'uniq_instructor_subject', columns: ['instructor_id', 'subject_id'])]
#[UniqueEntity(
    fields: ['instructor', 'subject'],
    message: 'This instructor is already assigned to this subject.'
)]
class InstructorAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Instructor is required.')]
    private ?User $instructor = null;

    #[ORM\ManyToOne(targetEntity: Subject::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Subject is required.')]
    private ?Subject $subject = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPrimary = true;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInstructor(): ?User
    {
        return $this->instructor;
    }

    public function setInstructor(?User $instructor): self
    {
        $this->instructor = $instructor;

        return $this;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(?Subject $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function __toString(): string
    {
        $instructorEmail = $this->instructor?->getEmail() ?? 'Unknown instructor';
        $subjectName = $this->subject?->getName() ?? 'Unknown subject';

        return sprintf('%s → %s', $instructorEmail, $subjectName);
    }
}
