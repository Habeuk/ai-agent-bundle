<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Repository;

use Habeuk\AiAgentBundle\Entity\Bot;
use Habeuk\AiAgentBundle\Entity\Message;
use Habeuk\HbkSymfony\Security\QueryFilter\EntityVisibilityFilter;
use Doctrine\Persistence\ManagerRegistry;
use Habeuk\HbkSymfony\Repository\BaseRepository;

/**
 *
 * @extends BaseRepository<Message>
 */
final class MessageRepository extends BaseRepository {

  public function __construct(ManagerRegistry $registry, EntityVisibilityFilter $entityVisibilityFilter) {
    parent::__construct($registry, Message::class, $entityVisibilityFilter);
  }

  /**
   * Récupère le fil de discussion complet d'un Bot dans l'ordre chronologique.
   *
   * @return list<Message>
   */
  public function findThreadByBot(Bot $bot): array {
    /** @var list<Message> $results */
    $results = $this->createQueryBuilder('m')
      ->where('m.bot = :bot')
      ->setParameter('bot', $bot)
      ->orderBy('m.createdAt', 'ASC')
      ->getQuery()
      ->getResult();

    return $results;
  }
}