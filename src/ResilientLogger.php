<?php

declare(strict_types=1);

namespace ResilientLogger;

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
    'environment' => 'dev',
    'origin' => 'unknown',
    'batch_limit' => 5000,
    'chunk_size' => 500,
    'store_old_entries_days' => 30,
  ];

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
   * @param ResilientLoggerOptions $options
   */
  static function create(array $options): static {
    $options = Helpers::mergeOptions($options, self::$DEFAULT_OPTIONS);

    /** @var class-string<AbstractLogSource>[] $sources */
    $sources = [];

    /** @var LogSourceConfig $sourceConfig */
    $sourceConfig = [
      "environment" => $options["environment"],
      "origin" => $options["origin"]
    ];

    if (empty($options["sources"])) {
      throw new \Exception("'sources' section of options is either missing or empty.");
    }

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

    if (empty($options["targets"])) {
      throw new \Exception("'targets' section of options is either missing or empty.");
    }

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