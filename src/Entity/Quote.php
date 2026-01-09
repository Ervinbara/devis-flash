<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\Table(name: 'quotes')]
#[ORM\HasLifecycleCallbacks]
class Quote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 50, unique: true, nullable: true)]
    private ?string $quoteNumber = null;

    // Entreprise (émetteur)
    #[Assert\NotBlank(message: 'Le nom de l\'entreprise est obligatoire')]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $companyName = null;

    #[Assert\NotBlank(message: 'Le contact est obligatoire')]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $companyContact = null;

    #[Assert\NotBlank(message: 'L\'adresse est obligatoire')]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $companyAddress = null;

    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'Email invalide')]
    #[ORM\Column(length: 255)]
    private ?string $companyEmail = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $companyPhone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $companySiret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyLogo = null;

    // Client
    #[Assert\NotBlank(message: 'Le nom du client est obligatoire')]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $clientName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientCompany = null;

    #[Assert\NotBlank(message: 'L\'adresse du client est obligatoire')]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $clientAddress = null;

    #[Assert\Email(message: 'Email client invalide')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientEmail = null;

    // Devis
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $quoteDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $quoteValidUntil = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $quoteDescription = null;

    #[Assert\NotBlank(message: 'Le taux de TVA est obligatoire')]
    #[Assert\Choice(choices: [0.0, 5.5, 10.0, 20.0], message: 'Taux de TVA invalide')]
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private $vatRate = 20.0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = 'Paiement à réception de facture. Règlement par virement bancaire.';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $pdfTemplate = 'modern';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private $totalHt = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private $totalTtc = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Items
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'Au moins une ligne de devis est requise')]
    #[ORM\OneToMany(targetEntity: QuoteItem::class, mappedBy: 'quote', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->quoteDate = new \DateTime();
        $this->quoteValidUntil = (new \DateTime())->modify('+30 days');
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters & Setters

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getQuoteNumber(): ?string { return $this->quoteNumber; }
    public function setQuoteNumber(?string $quoteNumber): self { $this->quoteNumber = $quoteNumber; return $this; }

    public function getCompanyName(): ?string { return $this->companyName; }
    public function setCompanyName(?string $companyName): self { $this->companyName = $companyName; return $this; }

    public function getCompanyContact(): ?string { return $this->companyContact; }
    public function setCompanyContact(?string $companyContact): self { $this->companyContact = $companyContact; return $this; }

    public function getCompanyAddress(): ?string { return $this->companyAddress; }
    public function setCompanyAddress(?string $companyAddress): self { $this->companyAddress = $companyAddress; return $this; }

    public function getCompanyEmail(): ?string { return $this->companyEmail; }
    public function setCompanyEmail(?string $companyEmail): self { $this->companyEmail = $companyEmail; return $this; }

    public function getCompanyPhone(): ?string { return $this->companyPhone; }
    public function setCompanyPhone(?string $companyPhone): self { $this->companyPhone = $companyPhone; return $this; }

    public function getCompanySiret(): ?string { return $this->companySiret; }
    public function setCompanySiret(?string $companySiret): self { $this->companySiret = $companySiret; return $this; }

    public function getCompanyLogo(): ?string { return $this->companyLogo; }
    public function setCompanyLogo(?string $companyLogo): self { $this->companyLogo = $companyLogo; return $this; }

    public function getClientName(): ?string { return $this->clientName; }
    public function setClientName(?string $clientName): self { $this->clientName = $clientName; return $this; }

    public function getClientCompany(): ?string { return $this->clientCompany; }
    public function setClientCompany(?string $clientCompany): self { $this->clientCompany = $clientCompany; return $this; }

    public function getClientAddress(): ?string { return $this->clientAddress; }
    public function setClientAddress(?string $clientAddress): self { $this->clientAddress = $clientAddress; return $this; }

    public function getClientEmail(): ?string { return $this->clientEmail; }
    public function setClientEmail(?string $clientEmail): self { $this->clientEmail = $clientEmail; return $this; }

    public function getQuoteDate(): ?\DateTimeInterface { return $this->quoteDate; }
    public function setQuoteDate(?\DateTimeInterface $quoteDate): self { $this->quoteDate = $quoteDate; return $this; }

    public function getQuoteValidUntil(): ?\DateTimeInterface { return $this->quoteValidUntil; }
    public function setQuoteValidUntil(?\DateTimeInterface $quoteValidUntil): self { $this->quoteValidUntil = $quoteValidUntil; return $this; }

    public function getQuoteDescription(): ?string { return $this->quoteDescription; }
    public function setQuoteDescription(?string $quoteDescription): self { $this->quoteDescription = $quoteDescription; return $this; }

    public function getVatRate(): float
    {
        if ($this->vatRate === null || $this->vatRate === '') {
            return 20.0;
        }
        return (float) $this->vatRate;
    }

    public function setVatRate($vatRate): self
    {
        $this->vatRate = $vatRate;
        return $this;
    }

    public function getPaymentTerms(): ?string { return $this->paymentTerms; }
    public function setPaymentTerms(?string $paymentTerms): self { $this->paymentTerms = $paymentTerms; return $this; }

    public function getPdfTemplate(): ?string { return $this->pdfTemplate; }
    public function setPdfTemplate(?string $pdfTemplate): self { $this->pdfTemplate = $pdfTemplate; return $this; }

    public function getTotalHt(): float
    {
        if ($this->totalHt === null || $this->totalHt === '') {
            return 0.0;
        }
        return (float) $this->totalHt;
    }

    public function setTotalHt($totalHt): self
    {
        $this->totalHt = $totalHt;
        return $this;
    }

    public function getTotalTtc(): float
    {
        if ($this->totalTtc === null || $this->totalTtc === '') {
            return 0.0;
        }
        return (float) $this->totalTtc;
    }

    public function setTotalTtc($totalTtc): self
    {
        $this->totalTtc = $totalTtc;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    /**
     * @return Collection<int, QuoteItem>
     */
    public function getItems(): Collection { return $this->items; }

    public function addItem(QuoteItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setQuote($this);
        }
        return $this;
    }

    public function removeItem(QuoteItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getQuote() === $this) {
                $item->setQuote(null);
            }
        }
        return $this;
    }

    public function calculateTotals(): void
    {
        $totalHt = array_reduce(
            $this->items->toArray(),
            fn($total, QuoteItem $item) => $total + $item->getTotalHt(),
            0.0
        );

        $this->totalHt = $totalHt;
        $vatAmount = $totalHt * ($this->vatRate / 100);
        $this->totalTtc = $totalHt + $vatAmount;
    }

    public function getVatAmount(): float
    {
        $totalHt = $this->totalHt !== null && $this->totalHt !== '' ? (float) $this->totalHt : $this->getTotalHtCalculated();
        $vatRate = $this->vatRate !== null && $this->vatRate !== '' ? (float) $this->vatRate : 0;
        return $totalHt * ($vatRate / 100);
    }

    private function getTotalHtCalculated(): float
    {
        return array_reduce(
            $this->items->toArray(),
            fn($total, QuoteItem $item) => $total + $item->getTotalHt(),
            0.0
        );
    }

    public function getTotalTtcCalculated(): float
    {
        return $this->getTotalHtCalculated() + $this->getVatAmount();
    }

    public function generateQuoteNumber(): string
    {
        if ($this->quoteNumber) {
            return $this->quoteNumber;
        }

        $date = $this->quoteDate ? $this->quoteDate->format('Ymd') : date('Ymd');
        $random = str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return "DF-{$date}-{$random}";
    }
}