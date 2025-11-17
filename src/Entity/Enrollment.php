<?php

namespace App\Entity;

use App\Repository\EnrollmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Enrollment date must be specified.")]
    #[Assert\Type("\DateTimeImmutable", message: "EnrolledAt must be a valid DateTime.")]
    private ?\DateTimeImmutable $enrolled_at = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Status is required.")]
    #[Assert\Length(max: 255, maxMessage: "Status cannot exceed {{ limit }} characters.")]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Course offering must be specified.")]
    private ?CourseOffering $offering = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnrolledAt(): ?\DateTimeImmutable
    {
        return $this->enrolled_at;
    }

    public function setEnrolledAt(\DateTimeImmutable $enrolled_at): static
    {
        $this->enrolled_at = $enrolled_at;
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

    public function getOffering(): ?CourseOffering
    {
        return $this->offering;
    }

    public function setOffering(?CourseOffering $offering): static
    {
        $this->offering = $offering;
        return $this;
    }
}
