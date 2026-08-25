<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AiUsageLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AiUsageLogRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_SCHOOL_ADMIN')"),
        new Get(security: "is_granted('ROLE_SCHOOL_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['ai_log:read']],
)]
class AiUsageLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ai_log:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Institution::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['ai_log:read'])]
    private ?Institution $institution = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['ai_log:read'])]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    #[Groups(['ai_log:read'])]
    private ?string $feature = null;

    #[ORM\Column]
    #[Groups(['ai_log:read'])]
    private int $promptTokens = 0;

    #[ORM\Column]
    #[Groups(['ai_log:read'])]
    private int $completionTokens = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 5, options: ['default' => '0.00000'])]
    #[Groups(['ai_log:read'])]
    private string $estimatedCost = '0.00000';

    #[ORM\Column]
    #[Groups(['ai_log:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInstitution(): ?Institution
    {
        return $this->institution;
    }

    public function setInstitution(?Institution $institution): static
    {
        $this->institution = $institution;

        return $this;
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

    public function getFeature(): ?string
    {
        return $this->feature;
    }

    public function setFeature(string $feature): static
    {
        $this->feature = $feature;

        return $this;
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function setPromptTokens(int $promptTokens): static
    {
        $this->promptTokens = $promptTokens;

        return $this;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function setCompletionTokens(int $completionTokens): static
    {
        $this->completionTokens = $completionTokens;

        return $this;
    }

    public function getEstimatedCost(): string
    {
        return $this->estimatedCost;
    }

    public function setEstimatedCost(string $estimatedCost): static
    {
        $this->estimatedCost = $estimatedCost;

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
}
