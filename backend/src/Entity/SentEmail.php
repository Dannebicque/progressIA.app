<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\SentEmailRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SentEmailRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_TEACHER')"),
        new Get(security: "is_granted('ROLE_TEACHER')"),
        new Delete(security: "is_granted('ROLE_TEACHER')"),
    ],
    normalizationContext: ['groups' => ['sent_email:read']],
    order: ['sentAt' => 'DESC'],
)]
class SentEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sent_email:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['sent_email:read'])]
    private ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['sent_email:read'])]
    private ?Session $session = null;

    #[ORM\Column(length: 255)]
    #[Groups(['sent_email:read'])]
    private ?string $subject = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['sent_email:read'])]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['sent_email:read'])]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['sent_email:read'])]
    private ?User $sender = null;

    #[ORM\Column(length: 100)]
    #[Groups(['sent_email:read'])]
    private ?string $targetGroup = null;

    #[ORM\Column]
    #[Groups(['sent_email:read'])]
    private int $recipientsCount = 0;

    /** @var array<string, string> */
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    #[Groups(['sent_email:read'])]
    private array $variables = [];

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

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

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getTargetGroup(): ?string
    {
        return $this->targetGroup;
    }

    public function setTargetGroup(string $targetGroup): static
    {
        $this->targetGroup = $targetGroup;

        return $this;
    }

    public function getRecipientsCount(): int
    {
        return $this->recipientsCount;
    }

    public function setRecipientsCount(int $recipientsCount): static
    {
        $this->recipientsCount = $recipientsCount;

        return $this;
    }

    /** @return array<string, string> */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /** @param array<string, string> $variables */
    public function setVariables(array $variables): static
    {
        $this->variables = $variables;

        return $this;
    }
}
