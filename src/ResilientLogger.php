<?php

declare(strict_types=1);

namespace ResilientLogger;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Targets\AbstractLogTarget;
use ResilientLogger\Types as Type;
use ResilientLogger\Sources\Types as SourceTypes;
use ResilientLogger\Utils\Helpers;

/**
 * @phpstan-import-type ResilientLoggerOptions from Types
 * @phpstan-import-type LogSourceConfig from SourceTypes
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

  private static ?LoggerInterface $internalLogger;

  /**
   * @param class-string<AbstractLogSource>[] $sources
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
  ) {}

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

  /**
   * @param ResilientLoggerOptions $options
   */
  static function create(array $options): static {
    $options = Helpers::mergeOptions($options, self::$DEFAULT_OPTIONS);

    foreach (static::getSchema() as $key => $tuple) {
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

    /** @var class-string<AbstractLogSource>[] $sources */
    $sources = [];

    /** @var LogSourceConfig $sourceConfig */
    $sourceConfig = [
      "environment" => $options["environment"],
      "origin" => $options["origin"]
    ];

    foreach ($options["sources"] as $source) {
      $sourceClassName = $source["class"];

      if (!is_subclass_of($sourceClassName, AbstractLogSource::class)) {
        throw new \Exception(sprintf("%s is not sub-class of AbstractLogSource", $sourceClassName));
      }

      $sourceClassName::configure($sourceConfig);
      $sources[] = $sourceClassName;
    }

    /** @var AbstractLogTarget[] $targets */
    $targets = [];

    foreach ($options["targets"] as $target) {
      $targetClassName = $target["class"];

      if (!is_subclass_of($targetClassName, AbstractLogTarget::class)) {
        throw new \Exception(sprintf("%s is not sub-class of AbstractLogTarget", $targetClassName));
      }

      $targets[] = $targetClassName::create($target);
    }

    return new static(
      $sources,
      $targets,
      $options["batch_limit"],
      $options["chunk_size"],
      $options["store_old_entries_days"]
    );
  }

  public static function setInternalLogger(LoggerInterface $logger): void {
    self::$internalLogger = $logger;
  }

  public static function getInternalLogger(): LoggerInterface {
    if (static::$internalLogger === null) {
      static::$internalLogger = new NullLogger();
    }

    return static::$internalLogger;
  }

  public function submit(AbstractLogSource $entry): bool {
    foreach ($this->targets as $target) {
      $submitted = $target->submit($entry);

      if (!$submitted && $target->isRequired()) {
        return false;
      }
    }

    return true;
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
   * @return \Generator<AbstractLogSource>
   */
  public function getUnsentEntries(): \Generator {
    foreach ($this->sources as $source) {
      foreach ($source::getUnsentEntries($this->chunkSize) as $entry) {
        yield $entry;
      }
    }
  }
  
  public function clearSentEntries(): void {
    foreach ($this->sources as $source) {
      $source::clearSentEntries($this->storeOldEntriesDays);
    }
  }
}