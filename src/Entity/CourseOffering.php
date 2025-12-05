<?php

namespace App\Entity;

use App\Repository\CourseOfferingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseOfferingRepository::class)]
class CourseOffering
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $term = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $capacity = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'courseOfferings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Section $section = null;

    #[ORM\ManyToOne(inversedBy: 'courseOfferings')]
    private ?Instructors $instructor = null;

    #[ORM\Column(length: 9)]
    private ?string $academic_year = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $schedule = null;

    /**
     * @var Collection<int, Enrollment>
     */
    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'offering')]
    private Collection $enrollments;

    #[ORM\ManyToOne(inversedBy:'subject')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Subject $subject = null;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTerm(): ?string
    {
        return $this->term;
    }

    public function setTerm(?string $term): static
    {
        $this->term = $term;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;

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

    public function getSection(): ?Section
    {
        return $this->section;
    }

    public function setSection(?Section $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getInstructor(): ?Instructors
    {
        return $this->instructor;
    }

    public function setInstructor(?Instructors $instructor): static
    {
        $this->instructor = $instructor;

        return $this;
    }

    public function getAcademicYear(): ?string
    {
        return $this->academic_year;
    }

    public function setAcademicYear(string $academic_year): static
    {
        $this->academic_year = $academic_year;

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

    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): static
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setOffering($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        if ($this->enrollments->removeElement($enrollment)) {
            // set the owning side to null (unless already changed)
            if ($enrollment->getOffering() === $this) {
                $enrollment->setOffering(null);
            }
        }

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
}
