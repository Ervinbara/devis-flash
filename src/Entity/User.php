<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $subscription = 'free'; // free, pro, pack

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $packCredits = 0; // Nombre de devis restants dans le pack

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $packExpiresAt = null; // Date d'expiration du pack (6 mois)

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: Quote::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $quotes;

    public function __construct()
    {
        $this->quotes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
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

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getSubscription(): ?string
    {
        return $this->subscription;
    }

    public function setSubscription(?string $subscription): static
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function isPro(): bool
    {
        return $this->subscription === 'pro' || $this->hasActivePackCredits();
    }

    public function getPackCredits(): ?int
    {
        return $this->packCredits;
    }

    public function setPackCredits(?int $packCredits): static
    {
        $this->packCredits = $packCredits;
        return $this;
    }

    public function getPackExpiresAt(): ?\DateTimeImmutable
    {
        return $this->packExpiresAt;
    }

    public function setPackExpiresAt(?\DateTimeImmutable $packExpiresAt): static
    {
        $this->packExpiresAt = $packExpiresAt;
        return $this;
    }

    /**
     * Vérifie si l'utilisateur a des crédits pack actifs (non expirés)
     */
    public function hasActivePackCredits(): bool
    {
        if ($this->packCredits === null || $this->packCredits <= 0) {
            return false;
        }

        if ($this->packExpiresAt === null) {
            return false;
        }

        // Vérifier si le pack n'est pas expiré
        return $this->packExpiresAt > new \DateTimeImmutable();
    }

    /**
     * Utilise un crédit du pack
     */
    public function usePackCredit(): bool
    {
        if (!$this->hasActivePackCredits()) {
            return false;
        }

        $this->packCredits--;
        return true;
    }

    /**
     * Ajoute des crédits au pack (lors de l'achat)
     */
    public function addPackCredits(int $credits, int $validityMonths = 6): static
    {
        $this->packCredits = ($this->packCredits ?? 0) + $credits;
        $this->packExpiresAt = (new \DateTimeImmutable())->modify("+{$validityMonths} months");
        $this->subscription = 'pack';
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, Quote>
     */
    public function getQuotes(): Collection
    {
        return $this->quotes;
    }

    public function addQuote(Quote $quote): static
    {
        if (!$this->quotes->contains($quote)) {
            $this->quotes->add($quote);
            $quote->setUser($this);
        }

        return $this;
    }

    public function removeQuote(Quote $quote): static
    {
        if ($this->quotes->removeElement($quote)) {
            // set the owning side to null (unless already changed)
            if ($quote->getUser() === $this) {
                $quote->setUser(null);
            }
        }

        return $this;
    }
}