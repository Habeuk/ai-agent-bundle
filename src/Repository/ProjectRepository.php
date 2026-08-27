<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Repository;

use Habeuk\AiAgentBundle\Entity\Project;
use App\Entity\User;
use Habeuk\HbkSymfony\Security\QueryFilter\EntityVisibilityFilter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Habeuk\HbkSymfony\Repository\BaseRepository;

/**
 *
 * @extends BaseRepository<Project>
 */
final class ProjectRepository extends BaseRepository {

  public function __construct(ManagerRegistry $registry, EntityVisibilityFilter $entityVisibilityFilter) {
    parent::__construct($registry, Project::class, $entityVisibilityFilter);
  }

  /**
   * Récupère un projet par son UUID en chargeant toutes ses relations clés (Paths, Bots, Resources)
   * afin d'éviter le problème N+1 lors des appels API.
   */
  public function findOneWithDetails(Uuid $id, User $user): ?Project {
    /** @var Project|null $project */
    $project = $this->createQueryBuilder('p')
      ->leftJoin('p.projectPaths', 'pp')
      ->leftJoin('p.bots', 'b')
      ->leftJoin('p.resources', 'r')
      ->addSelect('pp', 'b', 'r')
      ->where('p.id = :id')
      ->andWhere('p.owner = :user')
      ->setParameter('id', $id, 'uuid')
      ->setParameter('user', $user)
      ->getQuery()
      ->getOneOrNullResult();

    return $project;
  }

  /**
   * Récupère tous les projets actifs appartenant à un utilisateur spécifique.
   *
   * @return list<Project>
   */
  public function findActiveByOwner(User $user): array {
    /** @var list<Project> $results */
    $results = $this->createQueryBuilder('p')
      ->where('p.owner = :user')
      ->andWhere('p.isActive = :active')
      ->setParameter('user', $user)
      ->setParameter('active', true)
      ->orderBy('p.createdAt', 'DESC')
      ->getQuery()
      ->getResult();

    return $results;
  }
}