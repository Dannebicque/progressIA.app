<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ChoiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChoiceRepository::class)]
#[ORM\Table(name: 'answer_choice')]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_TEACHER')"),
        new Get(security: "is_granted('ROLE_TEACHER')", normalizationContext: ['groups' => ['eval:admin']]),
        new Post(security: "is_granted('ROLE_TEACHER')"),
        new Patch(security: "is_granted('ROLE_TEACHER')"),
        new Delete(security: "is_granted('ROLE_TEACHER')"),
    ],
    normalizationContext: ['groups' => ['choice:read']],
    denormalizationContext: ['groups' => ['choice:write']],
)]
class Choice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // Visible to students (so they can submit) — but `correct` is NOT.
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'choice:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'choices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['choice:write'])]
    private ?Question $question = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'choice:read', 'choice:write'])]
    private ?string $text = null;

    /**
     * The correct flag is exposed ONLY through teacher-only groups (eval:admin / choice:write),
     * never through the public course tree (course:read). Grading happens server-side.
     */
    #[ORM\Column(options: ['default' => false])]
    #[Groups(['eval:admin', 'choice:write'])]
    private bool $correct = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'choice:read', 'choice:write'])]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function isCorrect(): bool
    {
        return $this->correct;
    }

    public function setCorrect(bool $correct): static
    {
        $this->correct = $correct;

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
}
