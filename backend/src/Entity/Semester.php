<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\SemesterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SemesterRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_SCHOOL_ADMIN')"),
        new Patch(security: "is_granted('ROLE_SCHOOL_ADMIN')"),
        new Delete(security: "is_granted('ROLE_SCHOOL_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['semester:read']],
    denormalizationContext: ['groups' => ['semester:write']],
)]
class Semester
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['semester:read', 'user:read', 'course:read', 'institution:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['semester:read', 'semester:write', 'user:read', 'course:read', 'institution:read'])]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'semesters')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['semester:read', 'semester:write'])]
    private ?Institution $institution = null;

    /** @var Collection<int, Course> */
    #[ORM\ManyToMany(targetEntity: Course::class, mappedBy: 'semesters')]
    private Collection $courses;

    public function __construct()
    {
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

    public function getInstitution(): ?Institution
    {
        return $this->institution;
    }

    public function setInstitution(?Institution $institution): static
    {
        $this->institution = $institution;

        return $this;
    }

    /** @return Collection<int, Course> */
    public function getCourses(): Collection
    {
        return $this->courses;
    }
}
