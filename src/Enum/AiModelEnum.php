<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\Enum;

use Habeuk\AiAgentBundle\Entity\Bot;
use Habeuk\HbkSymfony\Enum\BaseEnumInterface;
use Habeuk\HbkSymfony\Enum\BaseEnumTrait;

/**
 * Catalogue centralisé des modèles d'IA pris en charge par l'application.
 *
 * IDs alignés sur les APIs officielles (DeepSeek & Google Gemini) — juillet 2026.
 *
 * Notes :
 * - deepseek-chat / deepseek-reasoner retirés le 24/07/2026
 * - gemini-2.0-* arrêtés le 01/06/2026
 * - gemini-2.5-* souvent indisponibles pour les nouveaux comptes AI Studio
 */
enum AiModelEnum: string implements BaseEnumInterface {
  use BaseEnumTrait;

  // -------------------------------------------------------------------------
  // DeepSeek (API : https://api.deepseek.com) — contexte 1M
  // -------------------------------------------------------------------------
  case DEEPSEEK_V4_FLASH = 'deepseek-v4-flash';

  case DEEPSEEK_V4_PRO = 'deepseek-v4-pro';

  // -------------------------------------------------------------------------
  // Google Gemini (API : generativelanguage.googleapis.com)
  // Famille 3.x — modèles disponibles pour les nouveaux utilisateurs
  // -------------------------------------------------------------------------
  case GEMINI_3_1_FLASH_LITE = 'gemini-3.1-flash-lite';

  case GEMINI_3_5_FLASH_LITE = 'gemini-3.5-flash-lite';

  case GEMINI_3_6_FLASH = 'gemini-3.6-flash';

  case GEMINI_3_5_FLASH = 'gemini-3.5-flash';

  case GEMINI_3_1_PRO_PREVIEW = 'gemini-3.1-pro-preview';

  /**
   * Modèle par défaut de l'application si aucun n'est spécifié.
   * Gemini 3.6 Flash : bon équilibre perf / coût / disponibilité.
   */
  public const DEFAULT_MODEL = self::GEMINI_3_6_FLASH;

  /**
   * Libellé humain pour l'affichage UI.
   */
  public function getLabel(): string {
    return match ($this) {
      self::DEEPSEEK_V4_FLASH => 'DeepSeek V4 Flash (Rapide & économique)',
      self::DEEPSEEK_V4_PRO => 'DeepSeek V4 Pro (Raisonnement avancé)',
      self::GEMINI_3_1_FLASH_LITE => 'Gemini 3.1 Flash-Lite (Économique)',
      self::GEMINI_3_5_FLASH_LITE => 'Gemini 3.5 Flash-Lite (Haut débit / faible latence)',
      self::GEMINI_3_6_FLASH => 'Gemini 3.6 Flash (Agentic & multimodal)',
      self::GEMINI_3_5_FLASH => 'Gemini 3.5 Flash (Coding & agents)',
      self::GEMINI_3_1_PRO_PREVIEW => 'Gemini 3.1 Pro Preview (Flagship)'
    };
  }

  /**
   * Provider associé au modèle.
   */
  public function getProvider(): string {
    return match ($this) {
      self::DEEPSEEK_V4_FLASH, self::DEEPSEEK_V4_PRO => Bot::PROVIDER_DEESEEK,

      self::GEMINI_3_1_FLASH_LITE, self::GEMINI_3_5_FLASH_LITE, self::GEMINI_3_6_FLASH, self::GEMINI_3_5_FLASH, self::GEMINI_3_1_PRO_PREVIEW => Bot::PROVIDER_GEMINI_GOOGLE
    };
  }

  /**
   * Indique si le modèle est encore en preview (à utiliser avec prudence en prod).
   */
  public function isPreview(): bool {
    return match ($this) {
      self::GEMINI_3_1_PRO_PREVIEW => true,
      default => false
    };
  }

  /**
   * Fenêtre de contexte maximale (tokens input), pour plafonner l'historique / fichiers.
   */
  public function getMaxContextWindow(): int {
    return match ($this) {
      // DeepSeek V4 : 1M tokens
      self::DEEPSEEK_V4_FLASH, self::DEEPSEEK_V4_PRO => 1_000_000,

      // Gemini 3.x : 1M tokens (output max typique ~65k)
      self::GEMINI_3_1_FLASH_LITE, self::GEMINI_3_5_FLASH_LITE, self::GEMINI_3_6_FLASH, self::GEMINI_3_5_FLASH, self::GEMINI_3_1_PRO_PREVIEW => 1_000_000
    };
  }

  /**
   * Options d'inférence au format Symfony AI (le bridge mappe vers l'API cible).
   *
   * @return array<string, mixed>
   */
  public function toInvocationOptions(?float $temperature = null, ?int $maxTokens = null): array {
    $temp = $temperature ?? 0.3;
    $tokens = $maxTokens ?? 4096;

    $options = match ($this->getProvider()) {
      'Google Gemini' => [
        'temperature' => $temp,
        'max_output_tokens' => $tokens
      ],
      'DeepSeek' => [
        'temperature' => $temp,
        'max_tokens' => $tokens
      ],
      default => throw new \InvalidArgumentException(sprintf('Provider non supporté pour le modèle "%s" : %s', $this->value, $this->getProvider()))
    };

    $options['stream'] = true;

    return $options;
  }

  /**
   * Liste brute des valeurs scalaires pour les assertions de validation Symfony.
   *
   * @return list<string>
   */
  public static function getValues(): array {
    return array_column(self::cases(), 'value');
  }
}