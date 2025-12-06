<?php

namespace App\Entity;

use App\Repository\ModuleRepository;
use App\Entity\Course;
use App\Entity\Subject;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
class Module
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // e.g. "IT 101 - Introduction to Computing (Section A)"
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Module name is required.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Module name cannot be longer than {{ limit }} characters.'
    )]
    private ?string $name = null;

    // e.g. "IT101-A"
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Module code is required.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Module code cannot be longer than {{ limit }} characters.'
    )]
    private ?string $code = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Year level is required.')]
    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: 'Year level must be between {{ min }} and {{ max }}.'
    )]
    private ?int $yearLevel = null;

    // 1 = 1st sem, 2 = 2nd sem (for now, simple int)
    #[ORM\Column]
    #[Assert\NotNull(message: 'Semester is required.')]
    #[Assert\Choice(
        choices: [1, 2],
        message: 'Semester must be either 1 or 2.'
    )]
    private ?int $semester = null;

    // Example: "MWF 1:00–2:00 PM, Room 101"
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Schedule cannot be longer than {{ limit }} characters.'
    )]
    private ?string $schedule = null;

    // Active / Closed (or Archived, etc. in the future)
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Status is required.')]
    #[Assert\Choice(
        choices: ['Active', 'Closed'],
        message: 'Status must be either "Active" or "Closed".'
    )]
    private ?string $status = 'Active';

    // --- Relations ---

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Course is required.')]
    private ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: Subject::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Subject is required.')]
    private ?Subject $subject = null;

    // Assigned instructor (User with ROLE_INSTRUCTOR)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $instructor = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getYearLevel(): ?int
    {
        return $this->yearLevel;
    }

    public function setYearLevel(int $yearLevel): static
    {
        $this->yearLevel = $yearLevel;

        return $this;
    }

    public function getSemester(): ?int
    {
        return $this->semester;
    }

    public function setSemester(int $semester): static
    {
        $this->semester = $semester;

        return $this;
    }

    public function getSchedule(): ?string
    {
        return $this->schedule;
    }

    public function setSchedule(?string $schedule): static
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(?Subject $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getInstructor(): ?User
    {
        return $this->instructor;
    }

    public function setInstructor(?User $instructor): static
    {
        $this->instructor = $instructor;

        return $this;
    }

    public function __toString(): string
    {
        $base = $this->code ?? $this->name ?? 'Module';

        return $base;
    }
}
