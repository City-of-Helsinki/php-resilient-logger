<?php

declare(strict_types=1);

namespace ResilientLogger;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Targets\AbstractLogTarget;
use ResilientLogger\Utils\Helpers;
use ResilientLogger\Utils\ReflectHelpers;

/**
 * @phpstan-import-type ResilientLoggerOptions from \ResilientLogger\Types
 * @phpstan-import-type LogSourceConfig from \ResilientLogger\Sources\Types
 */
class ResilientLogger {
  /**
   * This entry provides default values AND acts as a source of truth
   * for filtering out unknown entries out of user provided options.
   * 
   * @var ResilientLoggerOptions $DEFAULT_OPTIONS
   */
  private static array $DEFAULT_OPTIONS = [
    'sources' => [],
    'targets' => [],
    'environment' => '',
    'origin' => '',
    'batch_limit' => 5000,
    'chunk_size' => 500,
    'store_old_entries_days' => 30,
    'submit_unsent_entries' => false,
    'clear_sent_entries' => false,
  ];

  private static ?LoggerInterface $internalLogger = null;

  private bool $overrideRequired = false;

  /**
   * @param AbstractLogSource[] $sources
   * @param AbstractLogTarget[] $targets
   * @param int $batchLimit
   * @param int $chunkSize
   * @param int $storeOldEntriesDays
   */
  private function __construct(
    private array $sources,
    private array $targets,
    private int $batchLimit,
    private int $chunkSize,
    private int $storeOldEntriesDays,
  ) {
    $hasRequiredTargets = Helpers::arrayHasAny(
      $this->targets,
      fn(AbstractLogTarget $item) => $item->isRequired()
    );

    $this->overrideRequired = ($hasRequiredTargets === false);
  }

  /**
   * Define the configuration schema and validation rules.
   * * @return array<string, array{callable, string}>
   */
  private static function getSchema(): array {
      $isNonEmptyString = fn($input) => is_string($input) && trim($input) !== '';
      $isNonEmptyList   = fn($input) => is_array($input) && !empty($input);

      return [
          'sources'     => [$isNonEmptyList,   'non-empty array'],
          'targets'     => [$isNonEmptyList,   'non-empty array'],
          'origin'      => [$isNonEmptyString, 'non-empty string'],
          'environment' => [$isNonEmptyString, 'non-empty string'],
      ];
  }

  private static function getClassOrFactoryProp(array $config, string $context): mixed {
      return $config['factory'] ?? $config['class'] ?? throw new \InvalidArgumentException(
          "Log $context configuration missing 'factory' or 'class' key."
      );
  }

  /**
   * @param ResilientLoggerOptions $options
   */
  static function create(array $options): self {
    $options = Helpers::mergeOptions($options, self::$DEFAULT_OPTIONS);

    foreach (self::getSchema() as $key => $tuple) {
      // Destructure the tuple into meaningful variables
      [$validator, $label] = $tuple;
      if (!isset($options[$key]) || !$validator($options[$key])) {
        throw new \InvalidArgumentException(sprintf(
          "Configuration error: '%s' must be a %s.",
          $key,
          $label
        ));
      }
    }

    /** @var AbstractLogSource[] $sources */
    $sources = [];

    /** @var LogSourceConfig $commonConfig */
    $commonConfig = [
      "environment" => $options["environment"],
      "origin" => $options["origin"]
    ];

    foreach ($options["sources"] as $source) {
      $classOrFactory = self::getClassOrFactoryProp($source, "Source");
      $factory = ReflectHelpers::createFactory($classOrFactory, AbstractLogSource::class);
      $sources[] = $factory(array_merge($source, $commonConfig));
    }

    /** @var AbstractLogTarget[] $targets */
    $targets = [];

    foreach ($options["targets"] as $target) {
      $classOrFactory = self::getClassOrFactoryProp($target, "Target");
      $factory = ReflectHelpers::createFactory($classOrFactory, AbstractLogTarget::class);
      $targets[] = $factory(array_merge($target, $commonConfig));
    }

    return new ResilientLogger(
      $sources,
      $targets,
      $options["batch_limit"],
      $options["chunk_size"],
      $options["store_old_entries_days"]
    );
  }

  /**
   * Returns list of configured log sources
   * 
   * @return AbstractLogSource[]
   */
  public function getSources(): array {
    return $this->sources;
  }

  /**
   * Returns list of configured log targets
   * 
   * @return AbstractLogTarget[]
   */
  public function getTargets(): array {
    return $this->targets;
  }

  public static function setInternalLogger(LoggerInterface $logger): void {
    self::$internalLogger = $logger;
  }

  public static function getInternalLogger(): LoggerInterface {
    if (self::$internalLogger === null) {
      self::$internalLogger = new NullLogger();
    }

    return self::$internalLogger;
  }

  public function submit(AbstractLogSourceEntry $entry): bool {
    $success = true;

    foreach ($this->targets as $target) {
      $submitted = $target->submit($entry);
    
      if (!$submitted && ($target->isRequired() || $this->overrideRequired)) {
        $success = false;
      }
    }

    return $success;
  }

  /**
   * @return array<int|string, bool>
   */
  public function submitUnsentEntries(): array {
    $results = [];
    $count = 0;

    foreach ($this->getUnsentEntries() as $entry) {
      if ($count >= $this->batchLimit) {
        break;
      }

      $result = $this->submit($entry);

      if ($result) {
        $entry->markSent();
      }

      $results[$entry->getId()] = $result;
      $count++;
    }

    return $results;
  }

  /**
   * @return \Generator<AbstractLogSourceEntry>
   */
  public function getUnsentEntries(): \Generator {
    foreach ($this->sources as $source) {
      foreach ($source->getUnsentEntries($this->chunkSize) as $entry) {
        yield $entry;
      }
    }
  }
  
  public function clearSentEntries(): void {
    foreach ($this->sources as $source) {
      $source->clearSentEntries($this->storeOldEntriesDays);
    }
  }
}