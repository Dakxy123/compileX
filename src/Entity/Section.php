<?php

namespace App\Entity;

use App\Repository\SectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: SectionRepository::class)]
#[UniqueEntity('section_code', message: 'This section code is already in use.')]
#[UniqueEntity('academic_year', message: 'This academic year already exists for a section.')]
class Section
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(message:"Section code is required.")]
    #[Assert\Length(max: 20, maxMessage: "Section code cannot exceed {{ limit }} characters.")]
    private ?string $section_code = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotNull(message:"Year level must not be blank.")]
    #[Assert\Positive(message:"Year level must be a positive number.")]
    private ?int $year_level = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    #[Assert\NotNull(message:"Course program must be specified.")]
    private ?Course $course_program = null;

    #[ORM\Column]
    #[Assert\NotNull(message:"Active status must be specified.")]
    #[Assert\Type(type: "bool", message:"Active status must be a boolean.")]
    private ?bool $isActive = null;

    #[ORM\Column]
    #[Assert\NotNull(message:"Created date must be specified.")]
    #[Assert\Type("\DateTimeImmutable", message:"Created at must be a valid DateTimeImmutable.")]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    #[Assert\NotNull(message:"Updated date must be specified.")]
    #[Assert\Type("\DateTime", message:"Updated at must be a valid DateTime.")]
    private ?\DateTime $updated_at = null;

    #[ORM\Column(type: Types::SMALLINT, unique:true)]
    #[Assert\NotNull(message:"Academic year must be specified.")]
    #[Assert\Range(
        min: 2000,
        max: 2100,
        notInRangeMessage: 'Academic year must be between {{ min }} and {{ max }}.'
    )]
    private ?int $academic_year = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    #[Assert\NotNull(message:"Subject must be specified.")]
    private ?Subject $name = null;

    /**
     * @var Collection<int, CourseOffering>
     */
    #[ORM\OneToMany(targetEntity: CourseOffering::class, mappedBy: 'section')]
    private Collection $courseOfferings;

    /**
     * @var Collection<int, Student>
     */
    #[ORM\OneToMany(targetEntity: Student::class, mappedBy: 'section')]
    private Collection $students;

    public function __construct()
    {
        $this->courseOfferings = new ArrayCollection();
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSectionCode(): ?string
    {
        return $this->section_code;
    }

    public function setSectionCode(string $section_code): static
    {
        $this->section_code = $section_code;
        return $this;
    }

    public function getYearLevel(): ?int
    {
        return $this->year_level;
    }

    public function setYearLevel(int $year_level): static
    {
        $this->year_level = $year_level;
        return $this;
    }

    public function getCourseProgram(): ?Course
    {
        return $this->course_program;
    }

    public function setCourseProgram(?Course $course_program): static
    {
        $this->course_program = $course_program;
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

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getAcademicYear(): ?int
    {
        return $this->academic_year;
    }

    public function setAcademicYear(int $academic_year): static
    {
        $this->academic_year = $academic_year;
        return $this;
    }

    public function getName(): ?Subject
    {
        return $this->name;
    }

    public function setName(?Subject $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCourseOfferings(): Collection
    {
        return $this->courseOfferings;
    }

    public function addCourseOffering(CourseOffering $courseOffering): static
    {
        if (!$this->courseOfferings->contains($courseOffering)) {
            $this->courseOfferings->add($courseOffering);
            $courseOffering->setSection($this);
        }
        return $this;
    }

    public function removeCourseOffering(CourseOffering $courseOffering): static
    {
        if ($this->courseOfferings->removeElement($courseOffering)) {
            if ($courseOffering->getSection() === $this) {
                $courseOffering->setSection(null);
            }
        }
        return $this;
    }

    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setSection($this);
        }
        return $this;
    }

    public function removeStudent(Student $student): static
    {
        if ($this->students->removeElement($student)) {
            if ($student->getSection() === $this) {
                $student->setSection(null);
            }
        }
        return $this;
    }
}
