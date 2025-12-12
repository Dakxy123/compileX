<?php

namespace App\Entity;

use App\Repository\InstructorApplicationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InstructorApplicationRepository::class)]
class InstructorApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'instructorApplications')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'An applicant is required.')]
    private ?User $applicant = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Please explain why you want to become an instructor.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Your reason must be at least {{ limit }} characters long.',
        max: 2000,
        maxMessage: 'Your reason cannot be longer than {{ limit }} characters.'
    )]
    private ?string $reason = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please attach your portfolio file.')]
    private ?string $portfolioFilename = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Application status is required.')]
    #[Assert\Choice(
        choices: ['PENDING', 'APPROVED', 'REJECTED'],
        message: 'Invalid application status.'
    )]
    private ?string $status = 'PENDING';

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull(message: 'Created date must be set.')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\ManyToOne]
    private ?User $reviewedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'PENDING';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicant(): ?User
    {
        return $this->applicant;
    }

    public function setApplicant(?User $applicant): static
    {
        $this->applicant = $applicant;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getPortfolioFilename(): ?string
    {
        return $this->portfolioFilename;
    }

    public function setPortfolioFilename(?string $portfolioFilename): static
    {
        $this->portfolioFilename = $portfolioFilename;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;
        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;
        return $this;
    }
}
