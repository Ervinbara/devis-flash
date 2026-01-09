<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuoteItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteItemRepository::class)]
#[ORM\Table(name: 'quote_items')]
class QuoteItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quote::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Quote $quote = null;

    #[Assert\NotBlank(message: 'La désignation est obligatoire')]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[Assert\NotBlank(message: 'La quantité est obligatoire')]
    #[Assert\Positive(message: 'La quantité doit être positive')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private $quantity = 1;

    #[Assert\NotBlank(message: 'Le prix unitaire est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif ou nul')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private $unitPriceHt = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): self
    {
        $this->quote = $quote;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getQuantity(): float
    {
        if ($this->quantity === null || $this->quantity === '') {
            return 1.0;
        }
        return (float) $this->quantity;
    }

    public function setQuantity($quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPriceHt(): float
    {
        if ($this->unitPriceHt === null || $this->unitPriceHt === '') {
            return 0.0;
        }
        return (float) $this->unitPriceHt;
    }

    public function setUnitPriceHt($unitPriceHt): self
    {
        $this->unitPriceHt = $unitPriceHt;
        return $this;
    }

    public function getTotalHt(): float
    {
        return $this->getQuantity() * $this->getUnitPriceHt();
    }
}