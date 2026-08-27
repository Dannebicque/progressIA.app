<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_TEACHER')"),
        new Patch(security: "is_granted('ROLE_TEACHER')"),
        new Delete(security: "is_granted('ROLE_TEACHER')"),
    ],
    normalizationContext: ['groups' => ['course:read']],
    denormalizationContext: ['groups' => ['course:write']],
    order: ['id' => 'ASC'],
)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['course:read', 'session:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write', 'session:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $theme = null;

    /** back | front | fullstack | other — drives the themed badges. */
    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $category = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $context = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $accentColor = null;

    #[ORM\Column(length: 60, nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $level = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['course:read', 'course:write', 'session:read'])]
    private ?string $semester = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['course:read', 'course:write', 'session:read'])]
    private bool $visible = true;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course:read', 'course:write'])]
    private ?string $scenario = null;

    /** @var Collection<int, Session> */
    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'course', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Groups(['course:read'])]
    private Collection $sessions;

    /** @var Collection<int, EmailTemplate> */
    #[ORM\OneToMany(targetEntity: EmailTemplate::class, mappedBy: 'course', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $emailTemplates;

    /** @var Collection<int, SentEmail> */
    #[ORM\OneToMany(targetEntity: SentEmail::class, mappedBy: 'course', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sentEmails;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['course:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, Institution> */
    #[ORM\ManyToMany(targetEntity: Institution::class, inversedBy: 'courses')]
    #[ORM\JoinTable(name: 'course_institution')]
    #[Groups(['course:read', 'course:write'])]
    private Collection $institutions;

    /** @var Collection<int, Semester> */
    #[ORM\ManyToMany(targetEntity: Semester::class, inversedBy: 'courses')]
    #[ORM\JoinTable(name: 'course_semester')]
    #[Groups(['course:read', 'course:write'])]
    private Collection $semesters;

    /** @var Collection<int, Formation> */
    #[ORM\ManyToMany(targetEntity: Formation::class, inversedBy: 'courses')]
    #[ORM\JoinTable(name: 'course_formation')]
    #[Groups(['course:read', 'course:write'])]
    private Collection $formations;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'course_teacher')]
    #[Groups(['course:read', 'course:write'])]
    private Collection $teachers;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->emailTemplates = new ArrayCollection();
        $this->sentEmails = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable();
        $this->institutions = new ArrayCollection();
        $this->semesters = new ArrayCollection();
        $this->formations = new ArrayCollection();
        $this->teachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getAccentColor(): ?string
    {
        return $this->accentColor;
    }

    public function setAccentColor(?string $accentColor): static
    {
        $this->accentColor = $accentColor;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getScenario(): ?string
    {
        return $this->scenario;
    }

    public function setScenario(?string $scenario): static
    {
        $this->scenario = $scenario;

        return $this;
    }

    /** @return Collection<int, Session> */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setCourse($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            if ($session->getCourse() === $this) {
                $session->setCourse(null);
            }
        }

        return $this;
    }

    public function getSemester(): ?string
    {
        return $this->semester;
    }

    public function setSemester(?string $semester): static
    {
        $this->semester = $semester;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /** @return Collection<int, EmailTemplate> */
    public function getEmailTemplates(): Collection
    {
        return $this->emailTemplates;
    }

    /** @return Collection<int, SentEmail> */
    public function getSentEmails(): Collection
    {
        return $this->sentEmails;
    }

    /** @return Collection<int, Institution> */
    public function getInstitutions(): Collection
    {
        return $this->institutions;
    }

    public function addInstitution(Institution $institution): static
    {
        if (!$this->institutions->contains($institution)) {
            $this->institutions->add($institution);
        }

        return $this;
    }

    public function removeInstitution(Institution $institution): static
    {
        $this->institutions->removeElement($institution);

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
        }

        return $this;
    }

    public function removeSemester(Semester $semester): static
    {
        $this->semesters->removeElement($semester);

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
        }

        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        $this->formations->removeElement($formation);

        return $this;
    }

    /** @return Collection<int, User> */
    public function getTeachers(): Collection
    {
        return $this->teachers;
    }

    public function addTeacher(User $teacher): static
    {
        if (!$this->teachers->contains($teacher)) {
            $this->teachers->add($teacher);
        }

        return $this;
    }

    public function removeTeacher(User $teacher): static
    {
        $this->teachers->removeElement($teacher);

        return $this;
    }
}
