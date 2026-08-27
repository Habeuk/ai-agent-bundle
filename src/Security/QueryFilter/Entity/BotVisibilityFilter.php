<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Security\QueryFilter\Entity;

use Habeuk\AiAgentBundle\Entity\Bot;
use Habeuk\HbkSymfony\Security\QueryFilter\Entity\AbstractQueryVisibilityFilter;

final readonly class BotVisibilityFilter extends AbstractQueryVisibilityFilter {

  public function supports(string $entityClass): bool {
    return $entityClass === Bot::class;
  }
}