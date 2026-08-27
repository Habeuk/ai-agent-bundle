<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\DTO;

use Habeuk\HbkSymfony\Attribute\ {
  ColumnLabel,
  EntityCollectionReference
};
use Habeuk\AiAgentBundle\Entity\ {
  Project,
  ProjectPath,
  Bot
};
use Habeuk\HbkSymfony\Enum\ColumnType;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ {
  Collection,
  ArrayCollection
};
use Habeuk\HbkSymfony\DTO\BaseDto;

#[Map(target: Project::class)]
class ProjectDto extends BaseDto {

  function __construct() {
    $this->projectPaths = new ArrayCollection();
    $this->bots = new ArrayCollection();
  }

  /**
   *
   * @var Collection<int, \Habeuk\AiAgentBundle\Entity\ProjectPath>|array<int,int>
   */
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  #[ColumnLabel('lines object', order: 10)]
  #[EntityCollectionReference(entityClass: ProjectPath::class)]
  public Collection|array $projectPaths;

  /**
   *
   * @var Collection<int, \Habeuk\AiAgentBundle\Entity\Bot>|array<int,int>
   */
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  #[ColumnLabel('lines object', order: 10)]
  #[EntityCollectionReference(entityClass: Bot::class)]
  public Collection|array $bots;

  #[ColumnLabel('Nom du projet', type: ColumnType::TEXT, order: 1, sortable: true)]
  #[Assert\NotBlank(message: 'Le nom du projet est obligatoire', groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Assert\Length(max: 255, maxMessage: 'Le nom du projet ne peut pas dépasser 255 caractères', groups: [
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
  public ?string $name = null;

  #[ColumnLabel('Description', order: 2)]
  #[Groups([
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public ?string $description = null;

  // ========================
  // Métadonnées
  // ========================
  #[ColumnLabel('Date de création', type: ColumnType::DATETIME, order: 3, sortable: true)]
  #[Groups([
    self::REFERENCE,
    self::LIST,
    self::VIEW
  ])]
  public ?\DateTimeImmutable $createdAt = null;

  #[ColumnLabel('Dernière mise à jour', type: ColumnType::DATETIME, order: 4)]
  #[Groups([
    self::VIEW
  ])]
  public ?\DateTimeImmutable $updatedAt = null;

  // ========================
  // Méthodes Utilitaires
  // ========================
  public function getTitle(): string {
    return $this->name ?? '';
  }
}