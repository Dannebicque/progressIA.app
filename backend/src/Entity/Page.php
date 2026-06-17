<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\PageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'content_page')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_TEACHER')"),
        new Patch(security: "is_granted('ROLE_TEACHER')"),
        new Delete(security: "is_granted('ROLE_TEACHER')"),
    ],
    normalizationContext: ['groups' => ['page:read']],
    denormalizationContext: ['groups' => ['page:write']],
)]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'page:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Chapter::class, inversedBy: 'pages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['page:write'])]
    private ?Chapter $chapter = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'page:read', 'page:write'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'page:read', 'page:write'])]
    private ?string $content = '';

    /** Points awarded the first time a student completes this page. */
    #[ORM\Column(type: 'integer', options: ['default' => 5])]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'page:read', 'page:write'])]
    private int $points = 5;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'page:read', 'page:write'])]
    private int $position = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'page:read', 'page:write'])]
    private bool $visible = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChapter(): ?Chapter
    {
        return $this->chapter;
    }

    public function setChapter(?Chapter $chapter): static
    {
        $this->chapter = $chapter;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }
}
