<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\EvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        // Teacher view exposes the correct answers (eval:admin); students never get them.
        new Get(security: "is_granted('ROLE_TEACHER')", normalizationContext: ['groups' => ['eval:admin']]),
        new Post(security: "is_granted('ROLE_TEACHER')"),
        new Patch(security: "is_granted('ROLE_TEACHER')"),
        new Delete(security: "is_granted('ROLE_TEACHER')"),
    ],
    normalizationContext: ['groups' => ['eval:read']],
    denormalizationContext: ['groups' => ['eval:write']],
)]
class Evaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Chapter::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['eval:write'])]
    private ?Chapter $chapter = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'eval:write'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'eval:write'])]
    private ?string $description = null;

    /** Bonus points awarded the first time a student passes the evaluation. */
    #[ORM\Column(type: 'integer', options: ['default' => 20])]
    #[Groups(['course:read', 'eval:read', 'eval:admin', 'eval:write'])]
    private int $pointsReward = 20;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'eval:write'])]
    private int $position = 0;

    /** @var Collection<int, Question> */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'evaluation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin'])]
    private Collection $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPointsReward(): int
    {
        return $this->pointsReward;
    }

    public function setPointsReward(int $pointsReward): static
    {
        $this->pointsReward = $pointsReward;

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

    /** @return Collection<int, Question> */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setEvaluation($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question) && $question->getEvaluation() === $this) {
            $question->setEvaluation(null);
        }

        return $this;
    }

    public function getMaxScore(): int
    {
        $max = 0;
        foreach ($this->questions as $q) {
            $max += $q->getPoints();
        }

        return $max;
    }
}
