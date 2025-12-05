<?php

namespace App\Entity;

use App\Repository\SubjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[UniqueEntity('code', message: 'This subject code is already in use.')]
class Subject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Subject name should not be blank.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Subject name cannot be longer than {{ limit }} characters.'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Subject code is required.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Subject code cannot be longer than {{ limit }} characters.'
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

    #[ORM\Column]
    #[Assert\NotNull(message: 'Semester is required.')]
    #[Assert\Range(
        min: 1,
        max: 2,
        notInRangeMessage: 'Semester must be 1 or 2.'
    )]
    private ?int $semester = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'Description cannot be longer than {{ limit }} characters.'
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Course is required.')]
    private ?Course $course = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function __toString(): string
    {
        if ($this->name && $this->code) {
            return sprintf('%s (%s)', $this->name, $this->code);
        }

        return (string) ($this->name ?? 'Subject #'.$this->id);
    }
}
