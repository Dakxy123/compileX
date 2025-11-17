<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
#[UniqueEntity('student_id', message: 'This student ID is already in use.')]
#[UniqueEntity('email', message: 'This email is already registered.')]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Student ID must be specified.")]
    private ?int $student_id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "First name is required.")]
    #[Assert\Length(max: 100, maxMessage: "First name cannot exceed {{ limit }} characters.")]
    private ?string $fname = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50, maxMessage: "Middle name cannot exceed {{ limit }} characters.")]
    private ?string $mname = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Last name is required.")]
    #[Assert\Length(max: 100, maxMessage: "Last name cannot exceed {{ limit }} characters.")]
    private ?string $lname = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "Email is required.")]
    #[Assert\Email(message: "The email '{{ value }}' is not valid.")]
    private ?string $email = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Active status must be specified.")]
    #[Assert\Type(type: "bool", message: "Active status must be a boolean.")]
    private ?bool $isActive = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Created date must be specified.")]
    #[Assert\Type("\DateTimeImmutable", message: "CreatedAt must be a valid DateTimeImmutable.")]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Section $section = null;

    #[ORM\OneToOne(targetEntity: self::class, inversedBy: 'enrollment')]
    private ?self $student = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudentId(): ?int
    {
        return $this->student_id;
    }

    public function setStudentId(int $student_id): static
    {
        $this->student_id = $student_id;
        return $this;
    }

    public function getFname(): ?string
    {
        return $this->fname;
    }

    public function setFname(string $fname): static
    {
        $this->fname = $fname;
        return $this;
    }

    public function getMname(): ?string
    {
        return $this->mname;
    }

    public function setMname(?string $mname): static
    {
        $this->mname = $mname;
        return $this;
    }

    public function getLname(): ?string
    {
        return $this->lname;
    }

    public function setLname(string $lname): static
    {
        $this->lname = $lname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getSection(): ?Section
    {
        return $this->section;
    }

    public function setSection(?Section $section): static
    {
        $this->section = $section;
        return $this;
    }

    public function getStudent(): ?self
    {
        return $this->student;
    }

    public function setStudent(?self $student): static
    {
        $this->student = $student;
        return $this;
    }
}
