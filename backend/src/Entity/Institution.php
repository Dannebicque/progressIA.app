<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\InstitutionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InstitutionRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_SUPER_ADMIN')"),
        new Patch(security: "is_granted('ROLE_SUPER_ADMIN') or (is_granted('ROLE_SCHOOL_ADMIN') and object == user.getInstitution())"),
        new Delete(security: "is_granted('ROLE_SUPER_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['institution:read']],
    denormalizationContext: ['groups' => ['institution:write']],
)]
class Institution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['institution:read', 'user:read', 'course:read', 'semester:read', 'formation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['institution:read', 'institution:write', 'user:read', 'course:read', 'semester:read', 'formation:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Groups(['institution:read', 'institution:write'])]
    private string $subscriptionFee = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Groups(['institution:read', 'institution:write'])]
    private string $costPerStudent = '0.00';

    /** @var list<string> */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['institution:read', 'institution:write'])]
    private array $emailDomains = [];

    #[ORM\Column(length: 50, unique: true, nullable: true)]
    #[Groups(['institution:read', 'institution:write'])]
    private ?string $invitationCode = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['institution:read', 'institution:write'])]
    private bool $aiEnabled = false;

    #[ORM\Column(length: 20, options: ['default' => 'global'])]
    #[Groups(['institution:read', 'institution:write'])]
    private string $aiConfigType = 'global';

    #[ORM\Column(length: 30, options: ['default' => 'groq'])]
    #[Groups(['institution:read', 'institution:write'])]
    private string $aiProvider = 'groq';

    #[ORM\Column(length: 100, options: ['default' => 'llama-3.1-70b-versatile'])]
    #[Groups(['institution:read', 'institution:write'])]
    private string $aiModel = 'llama-3.1-70b-versatile';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['institution:read', 'institution:write'])]
    private ?string $aiApiKey = null;

    /** @var Collection<int, Semester> */
    #[ORM\OneToMany(targetEntity: Semester::class, mappedBy: 'institution', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $semesters;

    /** @var Collection<int, Formation> */
    #[ORM\OneToMany(targetEntity: Formation::class, mappedBy: 'institution', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $formations;

    /** @var Collection<int, User> */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'institution')]
    private Collection $users;

    /** @var Collection<int, Course> */
    #[ORM\ManyToMany(targetEntity: Course::class, mappedBy: 'institutions')]
    private Collection $courses;

    public function __construct()
    {
        $this->semesters = new ArrayCollection();
        $this->formations = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->courses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSubscriptionFee(): string
    {
        return $this->subscriptionFee;
    }

    public function setSubscriptionFee(string $subscriptionFee): static
    {
        $this->subscriptionFee = $subscriptionFee;

        return $this;
    }

    public function getCostPerStudent(): string
    {
        return $this->costPerStudent;
    }

    public function setCostPerStudent(string $costPerStudent): static
    {
        $this->costPerStudent = $costPerStudent;

        return $this;
    }

    /** @return Collection<int, Semester> */
    public function getSemesters(): Collection
    {
        return $this->semesters;
    }

    public function addSemester(Semester $semester): static
    {
        if (!$this->semesters->contains($semester)) {
            $this->semesters->add($semester);
            $semester->setInstitution($this);
        }

        return $this;
    }

    public function removeSemester(Semester $semester): static
    {
        if ($this->semesters->removeElement($semester)) {
            if ($semester->getInstitution() === $this) {
                $semester->setInstitution(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, Formation> */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formation $formation): static
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
            $formation->setInstitution($this);
        }

        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            if ($formation->getInstitution() === $this) {
                $formation->setInstitution(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, User> */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /** @return Collection<int, Course> */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    /** @return list<string> */
    public function getEmailDomains(): array
    {
        return $this->emailDomains;
    }

    /** @param list<string> $emailDomains */
    public function setEmailDomains(array $emailDomains): static
    {
        $this->emailDomains = $emailDomains;

        return $this;
    }

    public function getInvitationCode(): ?string
    {
        return $this->invitationCode;
    }

    public function setInvitationCode(?string $invitationCode): static
    {
        $this->invitationCode = $invitationCode;

        return $this;
    }

    public function isAiEnabled(): bool
    {
        return $this->aiEnabled;
    }

    public function setAiEnabled(bool $aiEnabled): static
    {
        $this->aiEnabled = $aiEnabled;

        return $this;
    }

    public function getAiConfigType(): string
    {
        return $this->aiConfigType;
    }

    public function setAiConfigType(string $aiConfigType): static
    {
        $this->aiConfigType = $aiConfigType;

        return $this;
    }

    public function getAiProvider(): string
    {
        return $this->aiProvider;
    }

    public function setAiProvider(string $aiProvider): static
    {
        $this->aiProvider = $aiProvider;

        return $this;
    }

    public function getAiModel(): string
    {
        return $this->aiModel;
    }

    public function setAiModel(string $aiModel): static
    {
        $this->aiModel = $aiModel;

        return $this;
    }

    public function getAiApiKey(): ?string
    {
        return $this->aiApiKey;
    }

    public function setAiApiKey(?string $aiApiKey): static
    {
        $this->aiApiKey = $aiApiKey;

        return $this;
    }
}
