<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Repository;

use Habeuk\AiAgentBundle\Entity\Bot;
use Habeuk\AiAgentBundle\Entity\Project;
use App\Security\QueryFilter\EntityVisibilityFilter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 *
 * @extends BaseRepository<Bot>
 */
final class BotRepository extends BaseRepository {

  public function __construct(ManagerRegistry $registry, EntityVisibilityFilter $entityVisibilityFilter) {
    parent::__construct($registry, Bot::class, $entityVisibilityFilter);
  }

  /**
   * Récupère un Bot avec l'intégralité de ses messages préchargés en une seule requête (anti N+1).
   */
  public function findOneWithMessages(Uuid $id): ?Bot {
    /** @var Bot|null $bot */
    $bot = $this->createQueryBuilder('b')
      ->leftJoin('b.messages', 'm')
      ->addSelect('m')
      ->where('b.id = :id')
      ->setParameter('id', $id, 'uuid')
      ->orderBy('m.createdAt', 'ASC')
      ->getQuery()
      ->getOneOrNullResult();

    return $bot;
  }

  /**
   * Récupère les bots non archivés d'un projet, priorisés par les épinglés et le dernier message.
   *
   * @return list<Bot>
   */
  public function findActiveByProject(Project $project): array {
    /** @var list<Bot> $results */
    $results = $this->createQueryBuilder('b')
      ->where('b.project = :project')
      ->andWhere('b.isArchived = :archived')
      ->setParameter('project', $project)
      ->setParameter('archived', false)
      ->orderBy('b.isPinned', 'DESC')
      ->addOrderBy('b.lastMessageAt', 'DESC')
      ->addOrderBy('b.createdAt', 'DESC')
      ->getQuery()
      ->getResult();

    return $results;
  }
}