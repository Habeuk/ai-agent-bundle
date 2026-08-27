<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\DTO;

use Habeuk\HbkSymfony\Attribute\ColumnLabel;
use Habeuk\AiAgentBundle\Entity\Message;
use Habeuk\HbkSymfony\Enum\ColumnType;
use Habeuk\AiAgentBundle\Enum\MessageRoleEnum;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Habeuk\HbkSymfony\DTO\BaseDto;

#[Map(target: Message::class)]
class MessageDto extends BaseDto {

  // =========================================================================
  // Contenu du message
  // =========================================================================
  #[ColumnLabel('Rôle du message', order: 1)]
  #[Assert\NotBlank(message: 'Le rôle est obligatoire', groups: [
    self::CREATE
  ])]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public MessageRoleEnum $role = MessageRoleEnum::DEFAULT;

  #[Groups([
    self::LIST,
    self::VIEW,
    self::EDIT,
    self::CREATE
  ])]
  #[ColumnLabel('Contenu du message', order: 2, type: ColumnType::RICH_TEXT, display: false)]
  #[Assert\NotBlank(message: 'Le message ne peut pas être vide', groups: [
    self::CREATE,
    self::EDIT
  ])]
  public ?string $content = null;

  /**
   * Retourne un résumé du document pour les listes
   */
  #[Groups([
    self::LIST
  ])]
  #[ColumnLabel('Resumé message', order: 2, type: ColumnType::TEXT)]
  public function getSummary(int $length = 180): string {
    if ($this->content === null || $this->content === '') {
      return '';
    }
    $summary = strip_tags($this->content);
    $summary = preg_replace('/\s+/u', ' ', trim($summary)) ?? '';
    if (mb_strlen($summary, 'UTF-8') > $length) {
      $summary = mb_substr($summary, 0, $length, 'UTF-8') . '...';
    }
    return $summary;
  }

  /**
   *
   * @var array<string, mixed>|null Appels de fonctions (Function Calling / Agents)
   */
  #[ColumnLabel('Tool Calls', type: ColumnType::JSON, order: 3)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?array $toolCalls = null;

  #[ColumnLabel('Tool Name', type: ColumnType::JSON, order: 3)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?string $toolName = null;

  #[ColumnLabel('Tool Call ID', type: ColumnType::TEXT, order: 4)]
  #[Groups([
    self::VIEW
  ])]
  public ?string $toolCallId = null;

  #[ColumnLabel('Modèle utilisé', type: ColumnType::TEXT, order: 5)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?string $model = null;

  // =========================================================================
  // Tokens
  // =========================================================================
  #[ColumnLabel('Tokens d\'entrée (Prompt)', type: ColumnType::NUMBER, order: 10)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?int $tokensInput = null;

  #[ColumnLabel('Tokens de sortie (Complétion)', type: ColumnType::NUMBER, order: 11)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?int $tokensOutput = null;

  #[ColumnLabel('Tokens Cache Hit', type: ColumnType::NUMBER, order: 12)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?int $tokensCacheHit = null;

  #[ColumnLabel('Tokens Cache Miss', type: ColumnType::NUMBER, order: 13)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?int $tokensCacheMiss = null;

  #[ColumnLabel('Tokens Reasoning', type: ColumnType::NUMBER, order: 14)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?int $tokensReasoning = null;

  #[ColumnLabel('Tokens Total', type: ColumnType::NUMBER, order: 15)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?int $tokensTotal = null;

  // =========================================================================
  // Coût & Contexte
  // =========================================================================
  #[ColumnLabel('Coût estimé (USD)', type: ColumnType::NUMBER, order: 20)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?float $estimatedCostUsd = null;

  #[ColumnLabel('% Contexte utilisé', type: ColumnType::NUMBER, order: 21)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?float $contextUsagePercent = null;

  #[ColumnLabel('Taux Cache Hit (%)', type: ColumnType::NUMBER, order: 22)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?float $cacheHitRate = null;

  // =========================================================================
  // Métadonnées de la requête
  // =========================================================================
  #[ColumnLabel('Request ID', type: ColumnType::TEXT, order: 30)]
  #[Groups([
    self::VIEW
  ])]
  public ?string $requestId = null;

  #[ColumnLabel('Finish Reason', type: ColumnType::TEXT, order: 31)]
  #[Groups([
    self::VIEW
  ])]
  public ?string $finishReason = null;

  #[ColumnLabel('System Fingerprint', type: ColumnType::TEXT, order: 32)]
  #[Groups([
    self::VIEW
  ])]
  public ?string $systemFingerprint = null;

  /**
   *
   * @var list<string>|null Liste des UUIDs ou chemins de fichiers injectés en contexte
   */
  #[ColumnLabel('Chemins de contexte utilisés', type: ColumnType::JSON, order: 33)]
  #[Groups([
    self::VIEW,
    self::CREATE
  ])]
  public ?array $referencedPaths = null;

  // =========================================================================
  // Métadonnées générales
  // =========================================================================
  #[ColumnLabel('Horodatage', type: ColumnType::DATETIME, order: 40, sortable: true)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?\DateTimeImmutable $createdAt = null;

  #[ColumnLabel('Status', type: ColumnType::BOOLEAN, order: 41)]
  #[Groups([
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public bool $status = true;

  // =========================================================================
  // Helpers
  // =========================================================================
  public function getTitle(): string {
    if ($this->content === null || $this->content === '') {
      return ucfirst($this->role->value);
    }

    return sprintf('%s: %s', ucfirst($this->role->value), mb_substr($this->content, 0, 30));
  }
}