<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ContactRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRequestRepository::class)]
#[ApiResource(
    operations: [
        new Post(security: "is_granted('PUBLIC_ACCESS')"),
        new GetCollection(security: "is_granted('ROLE_SUPER_ADMIN')"),
        new Get(security: "is_granted('ROLE_SUPER_ADMIN')"),
        new Patch(security: "is_granted('ROLE_SUPER_ADMIN')"),
        new Delete(security: "is_granted('ROLE_SUPER_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['contact:read']],
    denormalizationContext: ['groups' => ['contact:write']],
)]
class ContactRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['contact:read', 'contact:write'])]
    private string $name = '';

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['contact:read', 'contact:write'])]
    private string $email = '';

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Groups(['contact:read', 'contact:write'])]
    private string $institutionName = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['contact:read', 'contact:write'])]
    private string $message = '';

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    #[Groups(['contact:read', 'contact:write'])]
    private string $status = 'pending';

    #[ORM\Column]
    #[Groups(['contact:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getInstitutionName(): string
    {
        return $this->institutionName;
    }

    public function setInstitutionName(string $institutionName): static
    {
        $this->institutionName = $institutionName;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
