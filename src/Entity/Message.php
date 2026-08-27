<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Entity;

use Habeuk\HbkSymfony\Attribute\MenuFrontendConfig;
use Habeuk\AiAgentBundle\DTO\MessageDto;
use Habeuk\HbkSymfony\Enum\PermissionEnum;
use App\Enum\ScopeEnum;
use Habeuk\AiAgentBundle\Enum\MessageRoleEnum;
use Habeuk\AiAgentBundle\Repository\MessageRepository;
use Habeuk\HbkSymfony\Shared\Doctrine\AbstractBaseEntity;
use Habeuk\HbkSymfony\Shared\Doctrine\Traits\StatusTrait;
use Habeuk\HbkSymfony\Shared\Doctrine\Traits\TimestampableTrait;
use Habeuk\HbkSymfony\Shared\Doctrine\Traits\UuidTrait;
use App\Shared\Doctrine\Traits\OwnerTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;
use App\Contract\OwnerInterface;
use App\Contract\StatusEntityInterface;

#[Map(target: MessageDto::class)]
#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: '`message`')]
#[MenuFrontendConfig(enabled: true, label: "Messages", entity: "Message", icon: "pi pi-comments", order: 6, permissions: [
  PermissionEnum::VIEW,
  PermissionEnum::CREATE,
  PermissionEnum::EDIT,
  PermissionEnum::DELETE
], scope: ScopeEnum::GLOBAL, requireOwnership: true, roles: [
  "ROLE_USER"
])]
#[ORM\HasLifecycleCallbacks]
class Message extends AbstractBaseEntity implements OwnerInterface, StatusEntityInterface {
  use TimestampableTrait;
  use UuidTrait;
  use OwnerTrait;
  use StatusTrait;

  // === Role & Content ===
  #[ORM\Column(length: 30, enumType: MessageRoleEnum::class, options: [
    'default' => MessageRoleEnum::DEFAULT
  ])]
  #[Assert\NotBlank(message: 'Le role doit etre definit')]
  private MessageRoleEnum $role = MessageRoleEnum::DEFAULT;

  #[ORM\Column(type: Types::TEXT)]
  #[Assert\NotBlank(message: 'Le contenu du message ne peut pas être vide.')]
  private string $content = '';

  /**
   *
   * @var array<string, mixed>|null Stockage des appels de fonctions/outils (Agentic Function Calling)
   */
  #[ORM\Column(type: Types::JSON, nullable: true)]
  private ?array $toolCalls = null;

  /**
   * Le nom de la methode du TOOL.
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $toolCallId = null;

  /**
   * Nom de l'outil (ex: read_file, write_file...)
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $toolName = null;

  // === Model ===
  #[ORM\Column(length: 100, nullable: true)]
  private ?string $model = null;

  // === Tokens ===
  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  #[Assert\PositiveOrZero]
  private ?int $tokensInput = null;

  // prompt_tokens
  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  #[Assert\PositiveOrZero]
  private ?int $tokensOutput = null;

  // completion_tokens
  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  #[Assert\PositiveOrZero]
  private ?int $tokensCacheHit = null;

  // prompt_cache_hit_tokens
  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  #[Assert\PositiveOrZero]
  private ?int $tokensCacheMiss = null;

  // prompt_cache_miss_tokens
  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  #[Assert\PositiveOrZero]
  private ?int $tokensReasoning = null;

  // reasoning_tokens
  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  #[Assert\PositiveOrZero]
  private ?int $tokensTotal = null;

  // total_tokens

  // === Coût & Contexte ===
  #[ORM\Column(type: Types::FLOAT, nullable: true)]
  #[Assert\PositiveOrZero]
  private ?float $estimatedCostUsd = null;

  #[ORM\Column(type: Types::FLOAT, nullable: true)]
  #[Assert\Range(min: 0, max: 100)]
  private ?float $contextUsagePercent = null;

  #[ORM\Column(type: Types::FLOAT, nullable: true)]
  #[Assert\Range(min: 0, max: 100)]
  private ?float $cacheHitRate = null;

  // === Métadonnées de la requête ===
  #[ORM\Column(length: 100, nullable: true)]
  private ?string $requestId = null;

  // id de la completion DeepSeek
  #[ORM\Column(length: 30, nullable: true)]
  private ?string $finishReason = null;

  // stop, length, etc.
  #[ORM\Column(length: 100, nullable: true)]
  private ?string $systemFingerprint = null;

  /**
   *
   * @var list<string>|null IDs/chemins des ProjectPath injectés au moment de la génération
   */
  #[ORM\Column(type: Types::JSON, nullable: true)]
  private ?array $referencedPaths = null;

  // === Relation ===
  #[ORM\ManyToOne(targetEntity: Bot::class, inversedBy: 'messages')]
  #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
  #[Assert\NotNull(message: "Le bot doit etre definit")]
  private ?Bot $bot = null;

  public function __construct() {
    $now = new \DateTimeImmutable();
    $this->createdAt = $now;
    $this->updatedAt = $now;
  }

  // =========================================================================
  // Getters & Setters
  // =========================================================================
  public function getRole(): MessageRoleEnum {
    return $this->role;
  }

  public function setRole(MessageRoleEnum $role): static {
    $this->role = $role;
    return $this;
  }

  public function getContent(): string {
    return $this->content;
  }

  public function setContent(string $content): static {
    $this->content = $content;
    return $this;
  }

  /**
   *
   * @return array<string, mixed>|null
   */
  public function getToolCalls(): ?array {
    /**
     *
     * @deprecated code.
     */
    // Ce code est à supprimer des le 01/09/2026. avant la sauvegarde etait dans un double array.
    // [{"id":"call_00_ojAU5qF1eq3XRRyXHOGY8366","name":"list_files","arguments":{"relative_path":"src\/Shared\/Doctrine\/Traits"}}]
    if ($this->toolCalls !== null) {
      /**
       *
       * @var array<mixed> $toolCalls
       */
      $toolCalls = $this->toolCalls;
      return $toolCalls[0] ?? $toolCalls;
    }
    return $this->toolCalls;
  }

  /**
   * Helper PHPStan 8 pour récupérer les arguments de l'outil.
   *
   * @return array<string, mixed>
   */
  public function getToolArguments(): array {
    if (\is_array($this->toolCalls) && isset($this->toolCalls['arguments']) && \is_array($this->toolCalls['arguments'])) {
      /** @var array<string, mixed> */
      return $this->toolCalls['arguments'];
    }
    return [];
  }

  /**
   *
   * @param array<string, mixed>|null $toolCalls
   */
  public function setToolCalls(?array $toolCalls): static {
    $this->toolCalls = $toolCalls;
    return $this;
  }

  public function getToolCallId(): ?string {
    return $this->toolCallId;
  }

  public function setToolCallId(?string $toolCallId): static {
    $this->toolCallId = $toolCallId;
    return $this;
  }

  public function getModel(): ?string {
    return $this->model;
  }

  public function setModel(?string $model): static {
    $this->model = $model;
    return $this;
  }

  // --- Tokens ---
  public function getTokensInput(): ?int {
    return $this->tokensInput;
  }

  public function setTokensInput(?int $tokensInput): static {
    $this->tokensInput = $tokensInput;
    return $this;
  }

  public function getTokensOutput(): ?int {
    return $this->tokensOutput;
  }

  public function setTokensOutput(?int $tokensOutput): static {
    $this->tokensOutput = $tokensOutput;
    return $this;
  }

  public function getTokensCacheHit(): ?int {
    return $this->tokensCacheHit;
  }

  public function setTokensCacheHit(?int $tokensCacheHit): static {
    $this->tokensCacheHit = $tokensCacheHit;
    return $this;
  }

  public function getTokensCacheMiss(): ?int {
    return $this->tokensCacheMiss;
  }

  public function setTokensCacheMiss(?int $tokensCacheMiss): static {
    $this->tokensCacheMiss = $tokensCacheMiss;
    return $this;
  }

  public function getTokensReasoning(): ?int {
    return $this->tokensReasoning;
  }

  public function setTokensReasoning(?int $tokensReasoning): static {
    $this->tokensReasoning = $tokensReasoning;
    return $this;
  }

  public function getTokensTotal(): ?int {
    return $this->tokensTotal;
  }

  public function setTokensTotal(?int $tokensTotal): static {
    $this->tokensTotal = $tokensTotal;
    return $this;
  }

  // --- Coût & Contexte ---
  public function getEstimatedCostUsd(): ?float {
    return $this->estimatedCostUsd;
  }

  public function setEstimatedCostUsd(?float $estimatedCostUsd): static {
    $this->estimatedCostUsd = $estimatedCostUsd;
    return $this;
  }

  public function getContextUsagePercent(): ?float {
    return $this->contextUsagePercent;
  }

  public function setContextUsagePercent(?float $contextUsagePercent): static {
    $this->contextUsagePercent = $contextUsagePercent;
    return $this;
  }

  public function getCacheHitRate(): ?float {
    return $this->cacheHitRate;
  }

  public function setCacheHitRate(?float $cacheHitRate): static {
    $this->cacheHitRate = $cacheHitRate;
    return $this;
  }

  // --- Métadonnées de la requête ---
  public function getRequestId(): ?string {
    return $this->requestId;
  }

  public function setRequestId(?string $requestId): static {
    $this->requestId = $requestId;
    return $this;
  }

  public function getFinishReason(): ?string {
    return $this->finishReason;
  }

  public function setFinishReason(?string $finishReason): static {
    $this->finishReason = $finishReason;
    return $this;
  }

  public function getSystemFingerprint(): ?string {
    return $this->systemFingerprint;
  }

  public function setSystemFingerprint(?string $systemFingerprint): static {
    $this->systemFingerprint = $systemFingerprint;
    return $this;
  }

  /**
   *
   * @return list<string>|null
   */
  public function getReferencedPaths(): ?array {
    return $this->referencedPaths;
  }

  /**
   *
   * @param list<string>|null $referencedPaths
   */
  public function setReferencedPaths(?array $referencedPaths): static {
    $this->referencedPaths = $referencedPaths;
    return $this;
  }

  // --- Relation ---
  public function getBot(): ?Bot {
    return $this->bot;
  }

  public function setBot(?Bot $bot): static {
    $this->bot = $bot;
    return $this;
  }

  public function getToolName(): ?string {
    return $this->toolName;
  }

  public function setToolName(?string $toolName): static {
    $this->toolName = $toolName;
    return $this;
  }

  // --- Helper ---
  public function getTitle(): string {
    return sprintf('%s: %s', ucfirst($this->role->value), mb_substr($this->content, 0, 30));
  }
}