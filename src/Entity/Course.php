<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[UniqueEntity('name', message: 'This course name is already in use.')]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Course name should not be blank.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Course name cannot be longer than {{ limit }} characters.'
    )]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'Description cannot be longer than {{ limit }} characters.'
    )]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $IsActive = true;

    /**
     * @var Collection<int, Subject>
     */
    #[ORM\OneToMany(targetEntity: Subject::class, mappedBy: 'course')]
    private Collection $subjects;

    /**
     * @var Collection<int, StudentProfile>
     */
    #[ORM\OneToMany(targetEntity: StudentProfile::class, mappedBy: 'course')]
    private Collection $studentProfiles;

    public function __construct()
    {
        $this->subjects = new ArrayCollection();
        // Optional: ensure default active
        if ($this->IsActive === null) {
            $this->IsActive = true;
        }
        $this->studentProfiles = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->IsActive;
    }

    public function setIsActive(bool $IsActive): static
    {
        $this->IsActive = $IsActive;

        return $this;
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjects(): Collection
    {
        return $this->subjects;
    }

    public function addSubject(Subject $subject): static
    {
        if (!$this->subjects->contains($subject)) {
            $this->subjects->add($subject);
            $subject->setCourse($this);
        }

        return $this;
    }

    public function removeSubject(Subject $subject): static
    {
        if ($this->subjects->removeElement($subject)) {
            // set the owning side to null (unless already changed)
            if ($subject->getCourse() === $this) {
                $subject->setCourse(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) ($this->name ?? 'Course #'.$this->id);
    }

    /**
     * @return Collection<int, StudentProfile>
     */
    public function getStudentProfiles(): Collection
    {
        return $this->studentProfiles;
    }

    public function addStudentProfile(StudentProfile $studentProfile): static
    {
        if (!$this->studentProfiles->contains($studentProfile)) {
            $this->studentProfiles->add($studentProfile);
            $studentProfile->setCourse($this);
        }

        return $this;
    }

    public function removeStudentProfile(StudentProfile $studentProfile): static
    {
        if ($this->studentProfiles->removeElement($studentProfile)) {
            // set the owning side to null (unless already changed)
            if ($studentProfile->getCourse() === $this) {
                $studentProfile->setCourse(null);
            }
        }

        return $this;
    }
}
