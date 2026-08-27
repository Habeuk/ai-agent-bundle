<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Security\QueryFilter\Entity;

use Habeuk\AiAgentBundle\Entity\ProjectPath;
use Habeuk\HbkSymfony\Security\QueryFilter\Entity\AbstractQueryVisibilityFilter;

final readonly class ProjectPathVisibilityFilter extends AbstractQueryVisibilityFilter {

  public function supports(string $entityClass): bool {
    return $entityClass === ProjectPath::class;
  }
}