<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Entity;

use App\Attribute\MenuFrontendConfig;
use App\Enum\PermissionEnum;
use App\Enum\ScopeEnum;
use App\Contract\OwnerInterface;
use App\Contract\StatusEntityInterface;
use App\DTO\ProjectPathDto;
use App\Repository\ProjectPathRepository;
use App\Shared\Doctrine\AbstractBaseEntity;
use App\Shared\Doctrine\Traits\OwnerTrait;
use App\Shared\Doctrine\Traits\StatusTrait;
use App\Shared\Doctrine\Traits\TimestampableTrait;
use App\Shared\Doctrine\Traits\UuidTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[Map(target: ProjectPathDto::class)]
#[ORM\Entity(repositoryClass: ProjectPathRepository::class)]
#[ORM\Table(name: '`project_path`')]
#[MenuFrontendConfig(enabled: true, label: "Chemins du projet", entity: "ProjectPath", icon: "pi pi-code", order: 3, permissions: [
  PermissionEnum::VIEW,
  PermissionEnum::CREATE,
  PermissionEnum::EDIT,
  PermissionEnum::DELETE
], scope: ScopeEnum::GLOBAL, requireOwnership: true, roles: [
  "ROLE_USER"
])]
#[ORM\HasLifecycleCallbacks]
class ProjectPath extends AbstractBaseEntity implements StatusEntityInterface, OwnerInterface {
  use TimestampableTrait;
  use StatusTrait;
  use UuidTrait;
  use OwnerTrait;

  public const TYPE_DIRECTORY = 'directory';

  public const TYPE_FILE = 'file';

  #[ORM\Column(length: 1024)]
  #[Assert\NotBlank(message: 'Le chemin ne peut pas être vide.')]
  private string $path = '' {
    get => $this->path;
    set(string $value) {
      $this->path = trim($value);
    }
  }

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
  private string $label = '' {
    get => $this->label;
    set(string $value) {
      $this->label = trim($value);
    }
  }

  #[ORM\Column(length: 20)]
  #[Assert\Choice(choices: [
    self::TYPE_DIRECTORY,
    self::TYPE_FILE
  ], message: 'Le type doit être "directory" ou "file".')]
  private string $type = self::TYPE_DIRECTORY;

  #[ORM\Column(type: Types::BOOLEAN, options: [
    'default' => true
  ])]
  private bool $isReadOnly = true;

  /**
   *
   * @var Collection<int, Project>
   */
  #[ORM\ManyToMany(targetEntity: Project::class, mappedBy: 'projectPaths')]
  private Collection $projects;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $description = null;

  public function __construct() {
    $this->projects = new ArrayCollection();
    $now = new \DateTimeImmutable();
    $this->createdAt = $now;
    $this->updatedAt = $now;
  }

  // === Getters & Setters ===
  public function getPath(): string {
    return $this->path;
  }

  public function setPath(string $path): static {
    $this->path = $path;
    return $this;
  }

  public function getLabel(): string {
    return $this->label;
  }

  public function setLabel(string $label): static {
    $this->label = $label;
    return $this;
  }

  public function getType(): string {
    return $this->type;
  }

  public function setType(string $type): static {
    $this->type = $type;
    return $this;
  }

  public function isReadOnly(): bool {
    return $this->isReadOnly;
  }

  public function setIsReadOnly(bool $isReadOnly): static {
    $this->isReadOnly = $isReadOnly;
    return $this;
  }

  public function getTitle(): string {
    return $this->label;
  }

  public function getDescription(): ?string {
    return $this->description;
  }

  public function setDescription(?string $description): static {
    $this->description = $description;
    return $this;
  }

  /**
   *
   * @return Collection<int, Project>
   */
  public function getProjects(): Collection {
    return $this->projects;
  }

  public function addProject(Project $project): static {
    if (! $this->projects->contains($project)) {
      $this->projects->add($project);
      if (! $project->getProjectPaths()->contains($this)) {
        $project->addProjectPath($this);
      }
    }
    return $this;
  }

  public function removeProject(Project $project): static {
    if ($this->projects->removeElement($project)) {
      if ($project->getProjectPaths()->contains($this)) {
        $project->removeProjectPath($this);
      }
    }
    return $this;
  }

  public function clearProjects(): static {
    // Transformer en tableau pour éviter les problèmes d'itération
    foreach ($this->projects->toArray() as $project) {
      /** @var Project $project */
      $project->getProjectPaths()->removeElement($this);
    }
    $this->projects->clear();
    return $this;
  }
}