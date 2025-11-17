<?php

namespace App\Entity;

use App\Repository\InstructorsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: InstructorsRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]
class Instructors
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "First name should not be blank.")]
    private ?string $first_name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $middle_name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message:"Last name should not be blank.")]
    private ?string $last_name = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: "Email should not be blank.")]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email.")]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message:"Password is required.")]
    #[Assert\Regex(pattern:"/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/", message:"Password must be at least 8 characters long and contain both letters and numbers.")]
    private ?string $password = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message:"Experties should not be blank.")]
    private ?string $experties = null;

    #[ORM\Column]
    #[Assert\Type(type:"bool")]
    #[Assert\NotBlank(message:"Active status must be specified.")]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, CourseOffering>
     */
    #[ORM\OneToMany(targetEntity: CourseOffering::class, mappedBy: 'instructor')]
    private Collection $courseOfferings;

    public function __construct()
    {
        $this->courseOfferings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getMiddleName(): ?string
    {
        return $this->middle_name;
    }

    public function setMiddleName(string $middle_name): static
    {
        $this->middle_name = $middle_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getExperties(): ?string
    {
        return $this->experties;
    }

    public function setExperties(string $experties): static
    {
        $this->experties = $experties;

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
            $courseOffering->setInstructor($this);
        }

        return $this;
    }

    public function removeCourseOffering(CourseOffering $courseOffering): static
    {
        if ($this->courseOfferings->removeElement($courseOffering)) {
            // set the owning side to null (unless already changed)
            if ($courseOffering->getInstructor() === $this) {
                $courseOffering->setInstructor(null);
            }
        }

        return $this;
    }
}
