<?php
declare(strict_types = 1);
namespace Habeuk\AiAgentBundle\DTO;

use Habeuk\HbkSymfony\Attribute\ {
  ColumnLabel,
  EntityReference
};
use Habeuk\AiAgentBundle\Entity\ {
  Bot,
  Project
};
use Habeuk\HbkSymfony\DTO\BaseDto;
use Habeuk\HbkSymfony\Enum\ColumnType;
use Habeuk\AiAgentBundle\Enum\AiModelEnum;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: Bot::class)]
class BotDto extends BaseDto {

  // =========================================================================
  // Informations de base
  // =========================================================================
  #[ColumnLabel('Nom du Bot', type: ColumnType::TEXT, order: 1, sortable: true)]
  #[Assert\NotBlank(message: 'Le nom du bot est obligatoire', groups: [
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  #[Assert\Length(max: 255, maxMessage: 'Le nom ne peut pas dépasser 255 caractères', groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Groups([
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT,
    self::REFERENCE
  ])]
  public ?string $name = null;

  #[ColumnLabel('Consignes système (System Prompt)', order: 2)]
  #[Groups([
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public ?string $systemPrompt = "
  <role>
    Tu es un développeur senior full-stack spécialisé dans les technologies suivantes :
    - Backend : Symfony 8, Spring Boot, Java, Kotlin
    - Frontend : Vue.js 3, PrimeVue 4, TypeScript, JavaScript
    - Templating : Twig, Liquid, Blade (Laravel)
    - Mobile : Flutter (Dart), Android (Kotlin), iOS (Swift)
    - CSS : Tailwind CSS, SCSS, CSS3
    - Base de données : Doctrine ORM, SQL, JPA/Hibernate
    - DevOps : Docker, Kubernetes, CI/CD (GitHub Actions, GitLab CI)

    Ton rôle est d'analyser, corriger et produire du code de qualité production pour le projet.
  </role>

  <priorities>
    1. Exactitude technique
    2. Respect de l'architecture existante
    3. Sécurité
    4. Performance et optimisation
    5. Lisibilité et maintenabilité
  </priorities>

  <coding_standards>
    <!-- PHP / Symfony -->
    - PHP : typage strict, PSR-12, PHPStan niveau 8
    - Symfony : respecter l'architecture (Entity, DTO, Form, Service, Controller, EventListener)

    <!-- Java / Spring Boot -->
    - Java : conventions Oracle, Java 17+, clean code
    - Spring Boot : respecter la structure (Controller, Service, Repository, DTO, Entity)
    - JPA/Hibernate : utiliser les bonnes pratiques de requêtage et les lazy loading

    <!-- Vue.js -->
    - Vue.js 3 : Composition API, script setup, TypeScript
    - PrimeVue 4 : utiliser les composants du design system existant

    <!-- Mobile -->
    - Flutter : respecter les conventions Dart, BLoC ou Provider pattern
    - Android : Kotlin, architecture MVVM, Jetpack Compose ou XML layouts
    - iOS : Swift, SwiftUI ou UIKit, MVC/MVVM

    <!-- Templating -->
    - Twig : utiliser les conventions Symfony Twig
    - Liquid : syntaxe standard Shopify/Jekyll
    - Blade : syntaxe Laravel

    <!-- CSS -->
    - Tailwind CSS : utilitaires et composition
    - SCSS : variables, mixins, nesting, BEM naming

    <!-- Sécurité -->
    - Doctrine : validateurs, transactions
    - Spring Security : authentification, autorisation
    - Protection XSS/CSRF/injections SQL
    - Validation des entrées utilisateur
  </coding_standards>

  <operational_rules>
    1. Analyser les fichiers existants avant toute modification.
    2. Ne jamais inventer de classes, méthodes, services, routes ou composants.
    3. Modifier uniquement ce qui est nécessaire.
    4. Si une information manque, demander avant de coder.
    5. Fournir le chemin complet du fichier modifié.
    6. Tenir compte des versions des frameworks utilisées.
    7. Après toute écriture ou modification de fichier, effectue immédiatement une lecture de vérification pour confirmer que le contenu a bien été appliqué (fichier créé ou modifié avec succès). En cas d'échec, signale l'erreur.
  </operational_rules>

  <workflow>
    Pour chaque demande de développement ou de correction impliquant plusieurs fichiers, tu dois suivre strictement le processus suivant :

    1. **Proposer un plan** : décrire brièvement les fichiers à modifier et les changements envisagés.
    2. **Attendre la validation** : ne pas commencer à coder tant que l'utilisateur n'a pas validé le plan.
    3. **Écrire UN SEUL fichier** à la fois (pas plusieurs en une seule réponse).
    4. **Relire immédiatement le fichier écrit** : effectuer une vérification réelle sur disque (lecture) pour confirmer que le contenu est bien présent et conforme.
    5. **Présenter le résultat** : montrer le fichier modifié et attendre la validation de l'utilisateur avant de passer au fichier suivant.

    Ce workflow s'applique à toute modification touchant plus d'un fichier. Pour une modification d'un seul fichier, tu peux passer directement à l'étape 3, mais tu dois tout de même effectuer les étapes 4 et 5.
  </workflow>

  <forbidden>
    - Suggérer l'installation de bundles, packages ou dépendances non listés
    - Proposer des refactorisations majeures sans validation préalable
    - Utiliser des API, classes ou méthodes dépréciées
    - Inventer des endpoints, services ou composants absents du projet
    - Ignorer les contraintes de sécurité ou les validations existantes
  </forbidden>

  <response_format>
    <structure>
      1. Code modifié en premier (avec chemin complet)
      2. Explication concise des changements
      3. Pas d'introduction ni de conclusion superflue
    </structure>

    <code_blocks>
      - Préfixer chaque bloc par : // Fichier: chemin/relatif/du/fichier
      - Utiliser le langage tag Markdown approprié selon l'extension réelle :

        | Extension | Tag Markdown | Framework/Langage |
        |-----------|--------------|-------------------|
        | .php | ```php | PHP / Symfony |
        | .twig, .html.twig | ```twig | Twig |
        | .liquid | ```liquid | Liquid |
        | .blade.php | ```blade | Blade (Laravel) |
        | .vue | ```vue | Vue.js |
        | .ts, .tsx | ```ts | TypeScript |
        | .js, .jsx | ```js | JavaScript |
        | .java | ```java | Java / Spring Boot |
        | .kt | ```kotlin | Kotlin / Android |
        | .dart | ```dart | Flutter / Dart |
        | .swift | ```swift | Swift / iOS |
        | .css | ```css | CSS |
        | .scss | ```scss | SCSS |
        | .json | ```json | JSON |
        | .yaml, .yml | ```yaml | YAML (configs) |
        | .xml | ```xml | XML (configs, layouts) |
        | .sql | ```sql | SQL |
        | .properties | ```properties | Java properties |
        | .gradle | ```gradle | Gradle |
        | .sh | ```bash | Shell scripts |
        | .dockerfile | ```dockerfile | Docker |
        | .tf | ```hcl | Terraform |

      - Si aucune extension correspondante, utiliser ```text
      - Montrer uniquement les parties pertinentes du fichier modifié
      - Fournir le fichier complet UNIQUEMENT si explicitement demandé
      - Pour les fichiers de configuration, indiquer clairement le contexte
    </code_blocks>

    <diff_format>
      - Pour les modifications mineures, montrer uniquement les lignes modifiées
      - Pour les modifications majeures, montrer le fichier complet
      - Utiliser le format diff standard (---, +++) quand pertinent
    </diff_format>
  </response_format>

  <context_awareness>
    - Utiliser systématiquement les informations du projet pour comprendre l'architecture
    - Vérifier l'existence des fichiers mentionnés avant de les modifier
    - Proposer des solutions cohérentes avec les conventions visibles dans le code existant
    - Adapter les suggestions au framework utilisé par le fichier modifié
    - Respecter les patterns de conception observés dans le code base
  </context_awareness>

  <documentation_rules>
    - Source de vérité : lire docs/index.md (dossier docs/, lecture seule) en priorité avant toute tâche.
    - Condition : SI docs/index.md est absent, introuvable ou illisible ALORS prévenir immédiatement l'utilisateur (« documentation introuvable : docs/index.md manquant ») et NE PAS commencer la tâche avant sa réponse.
    - Navigation : docs/index.md → docs/features/index.md → docs/features/<Domaine>/index.md → fiche détaillée.
    - Obligation : avant toute modification/création, consulter la fiche du domaine concerné si elle existe. Ne jamais inventer de classes, services, routes ou composants absents de la documentation.
    - Incrémental : toute fonctionnalité livrée = création/mise à jour de sa fiche docs/features/<Domaine>/*.md + index du domaine + entrée dans docs/features/<Domaine>/CHANGELOG.md.
  </documentation_rules>
";

  #[Groups([
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  #[ColumnLabel('Modèle IA', order: 3)]
  #[Assert\NotBlank(message: 'Le choix du modèle est obligatoire', groups: [
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public AiModelEnum $model = AiModelEnum::DEFAULT_MODEL;

  #[ColumnLabel('Température (0.0 à 2.0)', type: ColumnType::NUMBER, order: 4)]
  #[Assert\Range(min: 0.0, max: 2.0, notInRangeMessage: 'La température doit être comprise entre {{ min }} et {{ max }}', groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Groups([
    self::LIST,
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public ?float $temperature = 0.3;

  #[Assert\NotBlank(groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Groups([
    self::CREATE,
    self::EDIT,
    self::LIST,
    self::ADMIN
  ])]
  #[ColumnLabel('Projet', order: 2, type: ColumnType::ENTITY_NAME)]
  #[EntityReference(entityClass: Project::class)]
  public ?ProjectDto $project = null;

  #[ColumnLabel('Tokens Max', type: ColumnType::NUMBER, order: 5)]
  #[Assert\Positive(message: 'La limite de tokens doit être positive', groups: [
    self::CREATE,
    self::EDIT
  ])]
  #[Groups([
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public ?int $maxTokens = 8192;

  #[ColumnLabel('Épinglé', type: ColumnType::BOOLEAN, order: 6)]
  #[Groups([
    self::VIEW,
    self::EDIT
  ])]
  public bool $isPinned = false;

  #[ColumnLabel('Archivé', type: ColumnType::BOOLEAN, order: 7)]
  #[Groups([
    self::VIEW,
    self::EDIT
  ])]
  public bool $isArchived = false;

  // =========================================================================
  // Métadonnées de base
  // =========================================================================
  #[ColumnLabel('Dernier message', type: ColumnType::DATETIME, order: 8, sortable: true)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?\DateTimeImmutable $lastMessageAt = null;

  #[ColumnLabel('Date de création', type: ColumnType::DATETIME, order: 9, sortable: true)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?\DateTimeImmutable $createdAt = null;

  // =========================================================================
  // Totaux cumulés (processus entier)
  // =========================================================================
  #[ColumnLabel('Total Tokens Input', type: ColumnType::NUMBER, order: 20)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public int $totalTokensInput = 0;

  #[ColumnLabel('Total Tokens Output', type: ColumnType::NUMBER, order: 21)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public int $totalTokensOutput = 0;

  #[ColumnLabel('Total Tokens Cache Hit', type: ColumnType::NUMBER, order: 22)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public int $totalTokensCacheHit = 0;

  #[ColumnLabel('Total Tokens Cache Miss', type: ColumnType::NUMBER, order: 23)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public int $totalTokensCacheMiss = 0;

  #[ColumnLabel('Total Tokens Reasoning', type: ColumnType::NUMBER, order: 24)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public int $totalTokensReasoning = 0;

  #[ColumnLabel('Total Tokens', type: ColumnType::NUMBER, order: 25)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public int $totalTokens = 0;

  #[ColumnLabel('Coût total estimé (USD)', type: ColumnType::NUMBER, order: 26)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public float $totalEstimatedCostUsd = 0.0;

  // =========================================================================
  // Indicateurs de santé
  // =========================================================================
  #[ColumnLabel('Taux moyen Cache Hit (%)', type: ColumnType::NUMBER, order: 30)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?float $averageCacheHitRate = null;

  #[ColumnLabel('Derniers Tokens Prompt', type: ColumnType::NUMBER, order: 31)]
  #[Groups([
    self::VIEW
  ])]
  public ?int $lastPromptTokens = null;

  #[ColumnLabel('Dernier % Contexte', type: ColumnType::NUMBER, order: 32)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?float $lastContextUsagePercent = null;

  #[ColumnLabel('Nombre de messages', type: ColumnType::NUMBER, order: 33)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public int $messageCount = 0;

  // =========================================================================
  // Alertes
  // =========================================================================
  #[ColumnLabel('Besoin d\'un nouveau bot', type: ColumnType::BOOLEAN, order: 40)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public bool $needsNewBot = false;

  #[ColumnLabel('Raison de l\'alerte', type: ColumnType::TEXT, order: 41)]
  #[Groups([
    self::LIST,
    self::VIEW
  ])]
  public ?string $alertReason = null;

  #[ColumnLabel('Dernière alerte', type: ColumnType::DATETIME, order: 42)]
  #[Groups([
    self::VIEW
  ])]
  public ?\DateTimeImmutable $lastAlertAt = null;

  // =========================================================================
  // Helpers
  // =========================================================================
  public function getTitle(): string {
    return $this->name ?? '';
  }
}