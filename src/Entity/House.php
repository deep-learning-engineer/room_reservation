<?php
namespace App\Entity;

use App\Repository\HouseRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ORM\Table(name: 'houses')]
class House
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $price;

    #[ORM\Column(type: 'boolean')]
    private bool $isAvailable = true;

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'house')]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self 
    { 
        $this->name = $name; return $this; 
    }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self 
    { 
        $this->description = $description; return $this; 
    }

    public function getPrice(): float { return $this->price; }
    public function setPrice(float $price): self 
    { 
        $this->price = $price; return $this; 
    }

    public function getIsAvailable(): bool { return $this->isAvailable; }
    public function setIsAvailable(bool $isAvailable): self 
    { 
        $this->isAvailable = $isAvailable; return $this; 
    }

    public function getBookings(): Collection { return $this->bookings; }
}