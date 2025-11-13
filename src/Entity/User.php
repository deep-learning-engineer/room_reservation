<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_PHONE', columns: ['phone'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    private string $email;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 11, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[78]\d{10}$/', message: 'Invalid phone number format')]
    private string $phone;

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'user')]
    private Collection $bookings;

    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getBookings(): Collection
    {
        return $this->bookings;
    }
}
