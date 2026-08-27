<?php
namespace Habeuk\AiAgentBundle\Entity;

use Habeuk\HbkSymfony\Attribute\MenuFrontendConfig;
use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Habeuk\HbkSymfony\Enum\ScopeEnum;
use Habeuk\AiAgentBundle\Repository\ProjectRepository;
use App\Shared\Doctrine\AbstractBaseEntity;
use App\Shared\Doctrine\Traits\OwnerTrait;
use App\Shared\Doctrine\Traits\ {
  TimestampableTrait,
  StatusTrait,
  UuidTrait
};
use App\Contract\OwnerInterface;
use App\Contract\StatusEntityInterface;
use Habeuk\AiAgentBundle\DTO\ProjectDto;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: ProjectDto::class)]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: '`project`')]
#[MenuFrontendConfig(enabled: true, label: "Projets", entity: "Project", icon: "pi pi-folder", order: 2, permissions: [
  PermissionEnum::VIEW,
  PermissionEnum::CREATE,
  PermissionEnum::EDIT,
  PermissionEnum::DELETE
], scope: ScopeEnum::GLOBAL, requireOwnership: true, roles: [
  "ROLE_USER"
])]
#[ORM\HasLifecycleCallbacks]
class Project extends AbstractBaseEntity implements StatusEntityInterface, OwnerInterface {
  use TimestampableTrait;
  use StatusTrait;
  use UuidTrait;
  use OwnerTrait;

  #[ORM\Column(length: 255)]
  private string $name = '';

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $description = null;

  /**
   *
   * @var Collection<int, ProjectPath>
   */
  #[ORM\ManyToMany(targetEntity: ProjectPath::class, inversedBy: 'projects')]
  #[ORM\JoinTable(name: 'project_project_path')]
  #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id')]
  #[ORM\InverseJoinColumn(name: 'project_path_id', referencedColumnName: 'id')]
  private Collection $projectPaths;

  /**
   *
   * @var Collection<int, Bot>
   */
  #[ORM\OneToMany(mappedBy: 'project', targetEntity: Bot::class, cascade: [
    'persist',
    'remove'
  ], orphanRemoval: true)]
  private Collection $bots;

  public function __construct() {
    $this->projectPaths = new ArrayCollection();
    $this->bots = new ArrayCollection();
    $now = new \DateTimeImmutable();
    $this->createdAt = $now;
    $this->updatedAt = $now;
  }

  // === Getters / Setters ===
  public function getName(): string {
    return $this->name;
  }

  public function setName(string $name): static {
    $this->name = $name;
    return $this;
  }

  public function getDescription(): ?string {
    return $this->description;
  }

  public function setDescription(?string $description): static {
    $this->description = $description;
    return $this;
  }

  // === ProjectPath ===

  /**
   *
   * @return Collection<int, ProjectPath>
   */
  public function getProjectPaths(): Collection {
    return $this->projectPaths;
  }

  public function addProjectPath(ProjectPath $projectPath): static {
    if (! $this->projectPaths->contains($projectPath)) {
      $this->projectPaths->add($projectPath);
      // Ajout symétrique (important pour la cohérence de l'ORM)
      if (! $projectPath->getProjects()->contains($this)) {
        $projectPath->addProject($this);
      }
    }
    return $this;
  }

  public function removeProjectPath(ProjectPath $projectPath): static {
    if ($this->projectPaths->removeElement($projectPath)) {
      // Suppression symétrique
      if ($projectPath->getProjects()->contains($this)) {
        $projectPath->removeProject($this);
      }
    }
    return $this;
  }

  public function clearProjectPaths(): static {
    foreach ($this->projectPaths->toArray() as $projectPath) {
      /** @var ProjectPath $projectPath */
      $projectPath->getProjects()->removeElement($this);
    }
    $this->projectPaths->clear();
    return $this;
  }

  // === Bot ===

  /**
   *
   * @return Collection<int, Bot>
   */
  public function getBots(): Collection {
    return $this->bots;
  }

  public function addBot(Bot $bot): static {
    if (! $this->bots->contains($bot)) {
      $this->bots->add($bot);
      $bot->setProject($this);
    }
    return $this;
  }

  public function removeBot(Bot $bot): static {
    if ($this->bots->removeElement($bot)) {
      if ($bot->getProject() === $this) {
        $bot->setProject(null);
      }
    }
    return $this;
  }

  public function clearBots(): static {
    foreach ($this->bots->toArray() as $bot) {
      /** @var Bot $bot */
      if ($bot->getProject() === $this) {
        $bot->setProject(null);
      }
    }
    $this->bots->clear();
    return $this;
  }

  /**
   *
   * @return Collection<int, Bot>
   */
  public function getActiveBots(): Collection {
    return $this->bots->filter(fn (Bot $bot) => ! $bot->isArchived());
  }

  // === Méthodes ===
  public function getTitle(): string {
    return $this->name;
  }
}