<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class Quote
{
    // Entreprise (émetteur)
    #[Assert\NotBlank(message: 'Le nom de l\'entreprise est obligatoire')]
    #[Assert\Length(max: 255)]
    private ?string $companyName = null;

    #[Assert\NotBlank(message: 'Le contact est obligatoire')]
    #[Assert\Length(max: 255)]
    private ?string $companyContact = null;

    #[Assert\NotBlank(message: 'L\'adresse est obligatoire')]
    private ?string $companyAddress = null;

    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'Email invalide')]
    private ?string $companyEmail = null;

    private ?string $companyPhone = null;
    private ?string $companySiret = null;
    private ?string $companyLogo = null;

    // Client
    #[Assert\NotBlank(message: 'Le nom du client est obligatoire')]
    #[Assert\Length(max: 255)]
    private ?string $clientName = null;

    private ?string $clientCompany = null;

    #[Assert\NotBlank(message: 'L\'adresse du client est obligatoire')]
    private ?string $clientAddress = null;

    #[Assert\Email(message: 'Email client invalide')]
    private ?string $clientEmail = null;

    // Devis
    private ?string $quoteNumber = null;

    #[Assert\NotBlank(message: 'La date est obligatoire')]
    private ?\DateTimeInterface $quoteDate = null;

    private ?\DateTimeInterface $quoteValidUntil = null;
    private ?string $quoteDescription = null;

    #[Assert\NotBlank(message: 'Le taux de TVA est obligatoire')]
    #[Assert\Choice(choices: [0, 5.5, 10, 20], message: 'Taux de TVA invalide')]
    private ?float $vatRate = 20.0;

    private ?string $paymentTerms = 'Paiement à réception de facture. Règlement par virement bancaire.';

    // Items
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'Au moins une ligne de devis est requise')]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->quoteDate = new \DateTime();
        $this->quoteValidUntil = (new \DateTime())->modify('+30 days');
    }

    // Getters & Setters Entreprise
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

    // Getters & Setters Client
    public function getClientName(): ?string { return $this->clientName; }
    public function setClientName(?string $clientName): self { $this->clientName = $clientName; return $this; }

    public function getClientCompany(): ?string { return $this->clientCompany; }
    public function setClientCompany(?string $clientCompany): self { $this->clientCompany = $clientCompany; return $this; }

    public function getClientAddress(): ?string { return $this->clientAddress; }
    public function setClientAddress(?string $clientAddress): self { $this->clientAddress = $clientAddress; return $this; }

    public function getClientEmail(): ?string { return $this->clientEmail; }
    public function setClientEmail(?string $clientEmail): self { $this->clientEmail = $clientEmail; return $this; }

    // Getters & Setters Devis
    public function getQuoteNumber(): ?string { return $this->quoteNumber; }
    public function setQuoteNumber(?string $quoteNumber): self { $this->quoteNumber = $quoteNumber; return $this; }

    public function getQuoteDate(): ?\DateTimeInterface { return $this->quoteDate; }
    public function setQuoteDate(?\DateTimeInterface $quoteDate): self { $this->quoteDate = $quoteDate; return $this; }

    public function getQuoteValidUntil(): ?\DateTimeInterface { return $this->quoteValidUntil; }
    public function setQuoteValidUntil(?\DateTimeInterface $quoteValidUntil): self { $this->quoteValidUntil = $quoteValidUntil; return $this; }

    public function getQuoteDescription(): ?string { return $this->quoteDescription; }
    public function setQuoteDescription(?string $quoteDescription): self { $this->quoteDescription = $quoteDescription; return $this; }

    public function getVatRate(): ?float { return $this->vatRate; }
    public function setVatRate(?float $vatRate): self { $this->vatRate = $vatRate; return $this; }

    public function getPaymentTerms(): ?string { return $this->paymentTerms; }
    public function setPaymentTerms(?string $paymentTerms): self { $this->paymentTerms = $paymentTerms; return $this; }

    // Items
    public function getItems(): Collection { return $this->items; }
    public function addItem(QuoteItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
        }
        return $this;
    }
    public function removeItem(QuoteItem $item): self
    {
        $this->items->removeElement($item);
        return $this;
    }

    // Calculs
    public function getTotalHt(): float
    {
        return array_reduce(
            $this->items->toArray(),
            fn($total, QuoteItem $item) => $total + $item->getTotalHt(),
            0.0
        );
    }

    public function getVatAmount(): float
    {
        return $this->getTotalHt() * ($this->vatRate ?? 0) / 100;
    }

    public function getTotalTtc(): float
    {
        return $this->getTotalHt() + $this->getVatAmount();
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
