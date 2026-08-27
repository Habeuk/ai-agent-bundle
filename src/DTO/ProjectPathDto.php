<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\DTO;

use App\Attribute\ {
  ColumnLabel,
  EntityReference,
  EntityCollectionReference
};
use Habeuk\AiAgentBundle\Entity\ {
  ProjectPath,
  Project
};
use App\Enum\ColumnType;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ {
  Collection,
  ArrayCollection
};
use Habeuk\HbkSymfony\DTO\BaseDto;

#[Map(target: ProjectPath::class)]
class ProjectPathDto extends BaseDto {

  function __construct() {
    $this->projects = new ArrayCollection();
  }

  #[ColumnLabel('Chemin local absolu', type: ColumnType::TEXT, order: 1, sortable: true)]
  #[Assert\NotBlank(message: 'Le chemin absolu est obligatoire', groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Assert\Length(max: 1024, maxMessage: 'Le chemin ne peut pas dépasser 1024 caractères', groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Groups([
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public ?string $path = null;

  #[ColumnLabel('Nom affiché', type: ColumnType::TEXT, order: 2, sortable: true)]
  #[Assert\NotBlank(message: 'Le nom affiché est obligatoire', groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Assert\Length(max: 255, maxMessage: 'Le libellé ne peut pas dépasser 255 caractères', groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Groups([
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT,
    self::REFERENCE
  ])]
  public ?string $label = null;

  #[ColumnLabel('Description', order: 2, display: false)]
  #[Groups([
    self::VIEW,
    self::CREATE,
    self::EDIT,
    self::LIST
  ])]
  public ?string $description = null;

  // #[Assert\NotBlank(groups: [
  // self::CREATE,
  // self::EDIT
  // ])]
  // #[Groups([
  // self::CREATE,
  // self::EDIT,
  // self::LIST,
  // self::ADMIN
  // ])]
  // #[ColumnLabel('Projet', order: 2, type: ColumnType::ENTITY_NAME)]
  // #[EntityReference(entityClass: Project::class)]
  // public ?ProjectDto $project = null;

  /**
   *
   * @var Collection<int, \App\Entity\Project>|array<int,int>
   */
  #[Groups([
    self::LIST,
    self::CREATE,
    self::EDIT,
    self::VIEW
  ])]
  #[ColumnLabel('lines object', order: 10, display: true)]
  #[EntityCollectionReference(entityClass: Project::class)]
  public Collection|array $projects;

  #[ColumnLabel('Type de ressource', order: 3)]
  #[Assert\NotBlank(message: 'Le type est obligatoire', groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Assert\Choice(choices: [
    ProjectPath::TYPE_DIRECTORY,
    ProjectPath::TYPE_FILE
  ], groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Groups([
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public string $type = ProjectPath::TYPE_DIRECTORY;

  #[ColumnLabel('Lecture seule (Sécurité IA)', type: ColumnType::BOOLEAN, order: 4)]
  #[Groups([
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public bool $isReadOnly = true;

  // ========================
  // Métadonnées
  // ========================
  #[ColumnLabel('Date d\'ajout', type: ColumnType::DATETIME, order: 5, sortable: true)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?\DateTimeImmutable $createdAt = null;

  public function getTitle(): string {
    return $this->label ?? $this->path ?? '';
  }
}