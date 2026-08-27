<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Repository;

use Habeuk\AiAgentBundle\Entity\Project;
use Habeuk\AiAgentBundle\Entity\ProjectPath;
use Habeuk\HbkSymfony\Security\QueryFilter\EntityVisibilityFilter;
use Doctrine\Persistence\ManagerRegistry;
use Habeuk\HbkSymfony\Repository\BaseRepository;

/**
 *
 * @extends BaseRepository<ProjectPath>
 */
final class ProjectPathRepository extends BaseRepository {

  public function __construct(ManagerRegistry $registry, EntityVisibilityFilter $entityVisibilityFilter) {
    parent::__construct($registry, ProjectPath::class, $entityVisibilityFilter);
  }

  /**
   * Récupère tous les chemins autorisés rattachés à un projet donné.
   *
   * @return list<ProjectPath>
   */
  public function findByProject(Project $project): array {
    /** @var list<ProjectPath> $results */
    $results = $this->createQueryBuilder('pp')
      ->where('pp.project = :project')
      ->setParameter('project', $project)
      ->orderBy('pp.type', 'ASC')
      ->
    // Dossiers en premier, puis fichiers
    addOrderBy('pp.label', 'ASC')
      ->getQuery()
      ->getResult();

    return $results;
  }
}