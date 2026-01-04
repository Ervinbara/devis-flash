<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class QuoteItem
{
    #[Assert\NotBlank(message: 'La désignation est obligatoire')]
    #[Assert\Length(max: 500)]
    private ?string $label = null;

    #[Assert\NotBlank(message: 'La quantité est obligatoire')]
    #[Assert\Positive(message: 'La quantité doit être positive')]
    private ?int $quantity = 1;

    #[Assert\NotBlank(message: 'Le prix unitaire est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif ou zéro')]
    private ?float $unitPriceHt = 0.0;

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPriceHt(): ?float
    {
        return $this->unitPriceHt;
    }

    public function setUnitPriceHt(?float $unitPriceHt): self
    {
        $this->unitPriceHt = $unitPriceHt;
        return $this;
    }

    public function getTotalHt(): float
    {
        return ($this->quantity ?? 0) * ($this->unitPriceHt ?? 0.0);
    }
}
