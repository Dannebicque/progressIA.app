<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(security: "is_granted('ROLE_TEACHER')", normalizationContext: ['groups' => ['eval:admin']]),
        new Post(security: "is_granted('ROLE_TEACHER')"),
        new Patch(security: "is_granted('ROLE_TEACHER')"),
        new Delete(security: "is_granted('ROLE_TEACHER')"),
    ],
    normalizationContext: ['groups' => ['question:read']],
    denormalizationContext: ['groups' => ['question:write']],
)]
class Question
{
    public const TYPE_QCM = 'qcm';
    public const TYPE_FREE = 'free';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'question:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Evaluation::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['question:write'])]
    private ?Evaluation $evaluation = null;

    /** qcm | free */
    #[ORM\Column(length: 10, options: ['default' => 'qcm'])]
    #[Assert\Choice(choices: [self::TYPE_QCM, self::TYPE_FREE])]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'question:read', 'question:write'])]
    private string $type = self::TYPE_QCM;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'question:read', 'question:write'])]
    private ?string $statement = null;

    /** Whether several choices can be correct (qcm only). */
    #[ORM\Column(options: ['default' => false])]
    #[Groups(['course:read', 'eval:read', 'eval:admin', 'question:read', 'question:write'])]
    private bool $multiple = false;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    #[Groups(['course:read', 'eval:read', 'eval:admin', 'question:read', 'question:write'])]
    private int $points = 1;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['course:read', 'eval:read', 'eval:admin', 'question:read', 'question:write'])]
    private int $position = 0;

    /** @var Collection<int, Choice> */
    #[ORM\OneToMany(targetEntity: Choice::class, mappedBy: 'question', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Groups(['course:read', 'session:read', 'chapter:read', 'eval:read', 'eval:admin', 'question:read'])]
    private Collection $choices;

    public function __construct()
    {
        $this->choices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluation(): ?Evaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?Evaluation $evaluation): static
    {
        $this->evaluation = $evaluation;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatement(): ?string
    {
        return $this->statement;
    }

    public function setStatement(string $statement): static
    {
        $this->statement = $statement;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): static
    {
        $this->multiple = $multiple;

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

    /** @return Collection<int, Choice> */
    public function getChoices(): Collection
    {
        return $this->choices;
    }

    public function addChoice(Choice $choice): static
    {
        if (!$this->choices->contains($choice)) {
            $this->choices->add($choice);
            $choice->setQuestion($this);
        }

        return $this;
    }

    public function removeChoice(Choice $choice): static
    {
        if ($this->choices->removeElement($choice) && $choice->getQuestion() === $this) {
            $choice->setQuestion(null);
        }

        return $this;
    }

    /** @return list<int> ids of the correct choices */
    public function correctChoiceIds(): array
    {
        $ids = [];
        foreach ($this->choices as $c) {
            if ($c->isCorrect()) {
                $ids[] = $c->getId();
            }
        }

        return $ids;
    }
}
