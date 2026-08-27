<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Entity;

use Habeuk\HbkSymfony\Attribute\MenuFrontendConfig;
use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Habeuk\HbkSymfony\Enum\ScopeEnum;
use Habeuk\AiAgentBundle\Enum\AiModelEnum;
use App\Contract\OwnerInterface;
use App\Contract\StatusEntityInterface;
use Habeuk\AiAgentBundle\DTO\BotDto;
use Habeuk\AiAgentBundle\Repository\BotRepository;
use App\Shared\Doctrine\AbstractBaseEntity;
use App\Shared\Doctrine\Traits\OwnerTrait;
use App\Shared\Doctrine\Traits\StatusTrait;
use App\Shared\Doctrine\Traits\TimestampableTrait;
use App\Shared\Doctrine\Traits\UuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: BotDto::class)]
#[ORM\Entity(repositoryClass: BotRepository::class)]
#[ORM\Table(name: '`bot`')]
#[MenuFrontendConfig(enabled: true, label: "Bots IA", entity: "Bot", icon: "pi pi-android", order: 5, permissions: [
  PermissionEnum::VIEW,
  PermissionEnum::CREATE,
  PermissionEnum::EDIT,
  PermissionEnum::DELETE
], scope: ScopeEnum::GLOBAL, requireOwnership: true, roles: [
  "ROLE_USER"
])]
#[ORM\HasLifecycleCallbacks]
class Bot extends AbstractBaseEntity implements StatusEntityInterface, OwnerInterface {
  use TimestampableTrait;
  use StatusTrait;
  use UuidTrait;
  use OwnerTrait;

  public const PROVIDER_DEESEEK = 'DeepSeek';

  public const PROVIDER_GEMINI_GOOGLE = 'Google Gemini';

  // === Informations de base ===
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'Le nom du bot est obligatoire.')]
  private string $name = '';

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $systemPrompt = null;

  #[ORM\Column(length: 100, enumType: AiModelEnum::class, options: [
    'default' => AiModelEnum::DEFAULT_MODEL
  ])]
  private AiModelEnum $model = AiModelEnum::DEFAULT_MODEL;

  #[ORM\Column(type: Types::FLOAT, nullable: true)]
  #[Assert\Range(min: 0.0, max: 2.0)]
  private ?float $temperature = 0.7;

  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  #[Assert\Positive]
  private ?int $maxTokens = 4096;

  #[ORM\Column(type: Types::BOOLEAN, options: [
    'default' => false
  ])]
  private bool $isPinned = false;

  #[ORM\Column(type: Types::BOOLEAN, options: [
    'default' => false
  ])]
  private bool $isArchived = false;

  #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
  private ?\DateTimeImmutable $lastMessageAt = null;

  // === Totaux cumulés (processus entier) ===
  #[ORM\Column(type: Types::INTEGER, options: [
    'default' => 0
  ])]
  private int $totalTokensInput = 0;

  #[ORM\Column(type: Types::INTEGER, options: [
    'default' => 0
  ])]
  private int $totalTokensOutput = 0;

  #[ORM\Column(type: Types::INTEGER, options: [
    'default' => 0
  ])]
  private int $totalTokensCacheHit = 0;

  #[ORM\Column(type: Types::INTEGER, options: [
    'default' => 0
  ])]
  private int $totalTokensCacheMiss = 0;

  #[ORM\Column(type: Types::INTEGER, options: [
    'default' => 0
  ])]
  private int $totalTokensReasoning = 0;

  #[ORM\Column(type: Types::INTEGER, options: [
    'default' => 0
  ])]
  private int $totalTokens = 0;

  #[ORM\Column(type: Types::FLOAT, options: [
    'default' => 0
  ])]
  private float $totalEstimatedCostUsd = 0.0;

  // === Indicateurs de santé du processus ===
  #[ORM\Column(type: Types::FLOAT, nullable: true)]
  #[Assert\Range(min: 0, max: 100)]
  private ?float $averageCacheHitRate = null;

  #[ORM\Column(type: Types::INTEGER, nullable: true)]
  private ?int $lastPromptTokens = null;

  #[ORM\Column(type: Types::FLOAT, nullable: true)]
  #[Assert\Range(min: 0, max: 100)]
  private ?float $lastContextUsagePercent = null;

  #[ORM\Column(type: Types::INTEGER, options: [
    'default' => 0
  ])]
  private int $messageCount = 0;

  // === Alertes / Décision de créer un nouveau bot ===
  #[ORM\Column(type: Types::BOOLEAN, options: [
    'default' => false
  ])]
  private bool $needsNewBot = false;

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $alertReason = null;

  #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
  private ?\DateTimeImmutable $lastAlertAt = null;

  // === Relations ===
  #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'bots')]
  #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
  #[Assert\NotNull(message: "Le projet doit etre definit")]
  private ?Project $project = null;

  /**
   *
   * @var Collection<int, Message>
   */
  #[ORM\OneToMany(mappedBy: 'bot', targetEntity: Message::class, cascade: [
    'persist',
    'remove'
  ], orphanRemoval: true)]
  #[ORM\OrderBy([
    'createdAt' => 'ASC'
  ])]
  private Collection $messages;

  public function __construct() {
    $this->messages = new ArrayCollection();
    $now = new \DateTimeImmutable();
    $this->createdAt = $now;
    $this->updatedAt = $now;
  }

  // =========================================================================
  // Getters & Setters - Informations de base
  // =========================================================================
  public function getName(): string {
    return $this->name;
  }

  public function setName(string $name): static {
    $this->name = trim($name);
    return $this;
  }

  public function getSystemPrompt(): ?string {
    return $this->systemPrompt;
  }

  public function getSystemPromptFull(): string {
    $systemPrompt = $this->getSystemPrompt() ?? "<role>Tu es un assistant IA utile et précis.</role>\n";
    return $this->defaultBaseSystemPrompt() . " " . $systemPrompt;
  }

  private function defaultBaseSystemPrompt(): string {
    return <<<'PROMPT'
     <!-- ============================================ -->
      <!-- RÈGLE PRIORITAIRE ABSOLUE                   -->
      <!-- ============================================ -->
      <write_authorization>
        <rule>
          L'écriture, la modification ou la création de tout fichier est STRICTEMENT INTERDITE sans accord explicite préalable de l'utilisateur.
        </rule>
        <process>
          1. Analyser la demande et identifier les fichiers concernés.
          2. Présenter à l'utilisateur la liste exhaustive des fichiers à créer/modifier.
          3. Pour chaque fichier, préciser la nature du changement (création, modification partielle, suppression).
          4. Attendre une réponse explicite de type "OUI", "VALIDE", "OK", "ACCORD", "GO".
          5. Ce n'est qu'après cette validation que l'écriture peut avoir lieu.
        </process>
        <strict_prohibition>
          - Ne JAMAIS considérer le silence comme un accord.
          - Ne JAMAIS regrouper plusieurs opérations sans validation individuelle.
          - Ne JAMAIS écrire un fichier non listé dans la demande de validation.
          - Ne JAMAIS modifier un fichier sans l'avoir listé au préalable.
          - En cas de doute, DEMANDER.
        </strict_prohibition>
      </write_authorization>
      <!-- ============================================ -->
    PROMPT;
  }

  public function setSystemPrompt(?string $systemPrompt): static {
    $this->systemPrompt = $systemPrompt;
    return $this;
  }

  public function getModel(): AiModelEnum {
    return $this->model;
  }

  public function setModel(AiModelEnum $model): static {
    $this->model = $model;
    return $this;
  }

  public function getTemperature(): ?float {
    return $this->temperature;
  }

  public function setTemperature(?float $temperature): static {
    $this->temperature = $temperature;
    return $this;
  }

  public function getMaxTokens(): ?int {
    return $this->maxTokens;
  }

  public function setMaxTokens(?int $maxTokens): static {
    $this->maxTokens = $maxTokens;
    return $this;
  }

  public function isPinned(): bool {
    return $this->isPinned;
  }

  public function setIsPinned(bool $isPinned): static {
    $this->isPinned = $isPinned;
    return $this;
  }

  public function isArchived(): bool {
    return $this->isArchived;
  }

  public function setIsArchived(bool $isArchived): static {
    $this->isArchived = $isArchived;
    return $this;
  }

  public function getLastMessageAt(): ?\DateTimeImmutable {
    return $this->lastMessageAt;
  }

  public function setLastMessageAt(?\DateTimeImmutable $lastMessageAt): static {
    $this->lastMessageAt = $lastMessageAt;
    return $this;
  }

  // =========================================================================
  // Getters & Setters - Totaux cumulés
  // =========================================================================
  public function getTotalTokensInput(): int {
    return $this->totalTokensInput;
  }

  public function setTotalTokensInput(int $totalTokensInput): static {
    $this->totalTokensInput = $totalTokensInput;
    return $this;
  }

  public function getTotalTokensOutput(): int {
    return $this->totalTokensOutput;
  }

  public function setTotalTokensOutput(int $totalTokensOutput): static {
    $this->totalTokensOutput = $totalTokensOutput;
    return $this;
  }

  public function getTotalTokensCacheHit(): int {
    return $this->totalTokensCacheHit;
  }

  public function setTotalTokensCacheHit(int $totalTokensCacheHit): static {
    $this->totalTokensCacheHit = $totalTokensCacheHit;
    return $this;
  }

  public function getTotalTokensCacheMiss(): int {
    return $this->totalTokensCacheMiss;
  }

  public function setTotalTokensCacheMiss(int $totalTokensCacheMiss): static {
    $this->totalTokensCacheMiss = $totalTokensCacheMiss;
    return $this;
  }

  public function getTotalTokensReasoning(): int {
    return $this->totalTokensReasoning;
  }

  public function setTotalTokensReasoning(int $totalTokensReasoning): static {
    $this->totalTokensReasoning = $totalTokensReasoning;
    return $this;
  }

  public function getTotalTokens(): int {
    return $this->totalTokens;
  }

  public function setTotalTokens(int $totalTokens): static {
    $this->totalTokens = $totalTokens;
    return $this;
  }

  public function getTotalEstimatedCostUsd(): float {
    return $this->totalEstimatedCostUsd;
  }

  public function setTotalEstimatedCostUsd(float $totalEstimatedCostUsd): static {
    $this->totalEstimatedCostUsd = $totalEstimatedCostUsd;
    return $this;
  }

  // =========================================================================
  // Getters & Setters - Indicateurs de santé
  // =========================================================================
  public function getAverageCacheHitRate(): ?float {
    return $this->averageCacheHitRate;
  }

  public function setAverageCacheHitRate(?float $averageCacheHitRate): static {
    $this->averageCacheHitRate = $averageCacheHitRate;
    return $this;
  }

  public function getLastPromptTokens(): ?int {
    return $this->lastPromptTokens;
  }

  public function setLastPromptTokens(?int $lastPromptTokens): static {
    $this->lastPromptTokens = $lastPromptTokens;
    return $this;
  }

  public function getLastContextUsagePercent(): ?float {
    return $this->lastContextUsagePercent;
  }

  public function setLastContextUsagePercent(?float $lastContextUsagePercent): static {
    $this->lastContextUsagePercent = $lastContextUsagePercent;
    return $this;
  }

  public function getMessageCount(): int {
    return $this->messageCount;
  }

  public function setMessageCount(int $messageCount): static {
    $this->messageCount = $messageCount;
    return $this;
  }

  // =========================================================================
  // Getters & Setters - Alertes
  // =========================================================================
  public function isNeedsNewBot(): bool {
    return $this->needsNewBot;
  }

  public function setNeedsNewBot(bool $needsNewBot): static {
    $this->needsNewBot = $needsNewBot;
    return $this;
  }

  public function getAlertReason(): ?string {
    return $this->alertReason;
  }

  public function setAlertReason(?string $alertReason): static {
    $this->alertReason = $alertReason;
    return $this;
  }

  public function getLastAlertAt(): ?\DateTimeImmutable {
    return $this->lastAlertAt;
  }

  public function setLastAlertAt(?\DateTimeImmutable $lastAlertAt): static {
    $this->lastAlertAt = $lastAlertAt;
    return $this;
  }

  // =========================================================================
  // Relations
  // =========================================================================
  public function getProject(): ?Project {
    return $this->project;
  }

  public function setProject(?Project $project): static {
    $this->project = $project;
    return $this;
  }

  /**
   *
   * @return Collection<int, Message>
   */
  public function getMessages(): Collection {
    return $this->messages;
  }

  public function addMessage(Message $message): static {
    if (! $this->messages->contains($message)) {
      $this->messages->add($message);
      $message->setBot($this);
      $this->lastMessageAt = new \DateTimeImmutable();
      $this->messageCount = $this->messages->count();
    }

    return $this;
  }

  public function removeMessage(Message $message): static {
    if ($this->messages->removeElement($message)) {
      if ($message->getBot() === $this) {
        $message->setBot(null);
      }
      $this->messageCount = $this->messages->count();
    }

    return $this;
  }

  // =========================================================================
  // Helpers
  // =========================================================================
  public function getTitle(): string {
    return $this->name;
  }

  /**
   * Met à jour les totaux à partir d'un Message (à appeler après chaque réponse IA)
   */
  public function updateStatsFromMessage(Message $message): static {
    $this->totalTokensInput += $message->getTokensInput() ?? 0;
    $this->totalTokensOutput += $message->getTokensOutput() ?? 0;
    $this->totalTokensCacheHit += $message->getTokensCacheHit() ?? 0;
    $this->totalTokensCacheMiss += $message->getTokensCacheMiss() ?? 0;
    $this->totalTokensReasoning += $message->getTokensReasoning() ?? 0;
    $this->totalTokens += $message->getTokensTotal() ?? 0;
    $this->totalEstimatedCostUsd += $message->getEstimatedCostUsd() ?? 0.0;
    $this->lastPromptTokens = $message->getTokensInput();
    $this->lastContextUsagePercent = $message->getContextUsagePercent();
    $this->messageCount = $this->messages->count();
    // Recalcul de la moyenne de cache hit
    if ($this->totalTokensInput > 0) {
      $this->averageCacheHitRate = round(($this->totalTokensCacheHit / $this->totalTokensInput) * 100, 2);
    }
    $this->evaluateHealth();
    return $this;
  }

  /**
   * Évalue si le bot a besoin d'être renouvelé
   */
  public function evaluateHealth(): void {
    $reasons = [];

    // 1. Contexte trop élevé
    if ($this->lastContextUsagePercent !== null && $this->lastContextUsagePercent > 35.0) {
      $reasons[] = sprintf('Contexte trop élevé (%.1f%%)', $this->lastContextUsagePercent);
    }

    // 2. Cache hit trop bas (après quelques messages)
    if ($this->averageCacheHitRate !== null && $this->averageCacheHitRate < 40.0 && $this->messageCount > 3) {
      $reasons[] = sprintf('Cache hit trop bas (%.1f%%)', $this->averageCacheHitRate);
    }

    // 3. Coût cumulé élevé (à ajuster selon ton budget)
    if ($this->totalEstimatedCostUsd > 0.50) {
      $reasons[] = sprintf('Coût cumulé élevé ($%.4f)', $this->totalEstimatedCostUsd);
    }

    // 4. Historique trop long
    if ($this->messageCount > 25) {
      $reasons[] = sprintf('Historique trop long (%d messages)', $this->messageCount);
    }

    if (! empty($reasons)) {
      $this->needsNewBot = true;
      $this->alertReason = implode(' | ', $reasons);
      $this->lastAlertAt = new \DateTimeImmutable();
    }
    else {
      $this->needsNewBot = false;
      $this->alertReason = null;
    }
  }
}