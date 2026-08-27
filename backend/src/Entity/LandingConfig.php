<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use App\Repository\LandingConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LandingConfigRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Put(security: "is_granted('ROLE_SUPER_ADMIN')"),
        new Patch(security: "is_granted('ROLE_SUPER_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['landing:read']],
    denormalizationContext: ['groups' => ['landing:write']],
)]
class LandingConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['landing:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['landing:read', 'landing:write'])]
    private string $heroTitle = 'Créez des cours engageants et gamifiés';

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    #[Groups(['landing:read', 'landing:write'])]
    private string $heroSubtitle = 'Rédigez en Markdown, suivez la progression et récompensez vos apprenants avec points & badges.';

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    #[Groups(['landing:read', 'landing:write'])]
    private array $plansJson = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeroTitle(): string
    {
        return $this->heroTitle;
    }

    public function setHeroTitle(string $heroTitle): static
    {
        $this->heroTitle = $heroTitle;

        return $this;
    }

    public function getHeroSubtitle(): string
    {
        return $this->heroSubtitle;
    }

    public function setHeroSubtitle(string $heroSubtitle): static
    {
        $this->heroSubtitle = $heroSubtitle;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getPlansJson(): array
    {
        return $this->plansJson;
    }

    /** @param array<string, mixed> $plansJson */
    public function setPlansJson(array $plansJson): static
    {
        $this->plansJson = $plansJson;

        return $this;
    }
}
