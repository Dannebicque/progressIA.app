<?php

namespace App\Entity;

use App\Repository\BadgeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BadgeRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_BADGE_USER_CODE', columns: ['user_id', 'code'])]
class Badge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'badge:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'badges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 60)]
    #[Groups(['user:read', 'badge:read'])]
    private string $code = '';

    #[ORM\Column(length: 120)]
    #[Groups(['user:read', 'badge:read'])]
    private string $label = '';

    /** Emoji shown with the badge. */
    #[ORM\Column(length: 16)]
    #[Groups(['user:read', 'badge:read'])]
    private string $icon = '🏅';

    #[ORM\Column(length: 200, nullable: true)]
    #[Groups(['user:read', 'badge:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['user:read', 'badge:read'])]
    private ?\DateTimeImmutable $awardedAt = null;

    public function __construct()
    {
        $this->awardedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAwardedAt(): ?\DateTimeImmutable
    {
        return $this->awardedAt;
    }

    public function setAwardedAt(\DateTimeImmutable $awardedAt): static
    {
        $this->awardedAt = $awardedAt;

        return $this;
    }
}
