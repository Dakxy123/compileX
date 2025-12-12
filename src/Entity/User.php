<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity('email', message: 'This email is already in use.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Email should not be blank.')]
    #[Assert\Email(message: "The email '{{ value }}' is not valid.")]
    private ?string $email = null;

    /**
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Password is required.')]
    #[Assert\Regex(
        pattern: "/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/",
        message: "Password must be at least 8 characters long and contain both letters and numbers."
    )]
    private ?string $password = null;

    /**
     * @var Collection<int, InstructorApplication>
     */
    #[ORM\OneToMany(targetEntity: InstructorApplication::class, mappedBy: 'applicant')]
    private Collection $instructorApplications;

    public function __construct()
    {
        $this->instructorApplications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
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

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // Not storing temporary sensitive info, so leave empty
    }

    /**
     * @return Collection<int, InstructorApplication>
     */
    public function getInstructorApplications(): Collection
    {
        return $this->instructorApplications;
    }

    public function addInstructorApplication(InstructorApplication $instructorApplication): static
    {
        if (!$this->instructorApplications->contains($instructorApplication)) {
            $this->instructorApplications->add($instructorApplication);
            $instructorApplication->setApplicant($this);
        }

        return $this;
    }

    public function removeInstructorApplication(InstructorApplication $instructorApplication): static
    {
        if ($this->instructorApplications->removeElement($instructorApplication)) {
            // set the owning side to null (unless already changed)
            if ($instructorApplication->getApplicant() === $this) {
                $instructorApplication->setApplicant(null);
            }
        }

        return $this;
    }
}
