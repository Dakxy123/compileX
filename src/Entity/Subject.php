<?php

namespace App\Entity;

use App\Repository\SubjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Subject title is required.")]
    #[Assert\Length(max: 100, maxMessage: "Subject title cannot exceed {{ limit }} characters.")]
    private ?string $title = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(message: "Subject code is required.")]
    #[Assert\Length(max: 20, maxMessage: "Subject code cannot exceed {{ limit }} characters.")]
    private ?string $code = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Active status must be specified.")]
    private ?bool $is_active = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Positive(message: "Units must be a positive number.")]
    private ?int $units = null;

    /**
     * @var Collection<int, Section>
     */
    #[ORM\OneToMany(targetEntity: Section::class, mappedBy: 'name')]
    private Collection $sections;

    /**
     * @var Collection<int, CourseOffering>
     */
    #[ORM\OneToMany(targetEntity: CourseOffering::class, mappedBy: 'subject')]
    private Collection $courseOfferings;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
        $this->courseOfferings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getUnits(): ?int
    {
        return $this->units;
    }

    public function setUnits(?int $units): static
    {
        $this->units = $units;
        return $this;
    }

    /**
     * @return Collection<int, Section>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(Section $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setName($this);
        }
        return $this;
    }

    public function removeSection(Section $section): static
    {
        if ($this->sections->removeElement($section)) {
            if ($section->getName() === $this) {
                $section->setName(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, CourseOffering>
     */
    public function getCourseOfferings(): Collection
    {
        return $this->courseOfferings;
    }

    public function addCourseOffering(CourseOffering $courseOffering): static
    {
        if (!$this->courseOfferings->contains($courseOffering)) {
            $this->courseOfferings->add($courseOffering);
            $courseOffering->setSubject($this);
        }
        return $this;
    }

    public function removeCourseOffering(CourseOffering $courseOffering): static
    {
        if ($this->courseOfferings->removeElement($courseOffering)) {
            if ($courseOffering->getSubject() === $this) {
                $courseOffering->setSubject(null);
            }
        }
        return $this;
    }
}
