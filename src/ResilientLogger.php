<?php

declare(strict_types=1);

namespace ResilientLogger;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Targets\AbstractLogTarget;

class ResilientLogger {
  private const DEFAULT_OPTIONS = [
    'sources' => [
      [ "class" => 'Path\To\LogSource' ]
    ],
    'targets' => [
      [ "class" => 'Path\To\LogTarget' ]
    ],
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
   * @param array{
   *   sources: array<
   *     array{
   *       class: class-string<AbstractLogSource>,
   *     }
   *   >,
   *   targets: array<
   *     array{
   *       class: class-string<AbstractLogTarget>,
   *     }
   *   >,
   *   store_old_entries_days: int,
   *   batch_limit: int,
   *   chunk_size: int,
   * } $options
   */
  static function create(array $options) {
    $options = self::mergeOptions($options, self::DEFAULT_OPTIONS);

    /** @var class-string<AbstractLogSource>[] $sources */
    $sources = [];

    /** @var AbstractLogTarget[] $targets */
    $targets = [];

    foreach ($options["sources"] as $source) {
      /** @var class-string<AbstractLogSource> $sourceClassName */
      $sourceClassName = $source["class"];

      if (!is_subclass_of($sourceClassName, AbstractLogSource::class)) {
        throw new \Exception(sprintf("%s is not sub-class of AbstractLogSource", $sourceClassName));
      }

      $sources[] = $sourceClassName;
    }

    foreach ($options["targets"] as $target) {
      /** @var class-string<AbstractLogTarget> $targetClassName */
      $targetClassName = $target["class"];

      if (!is_subclass_of($targetClassName, AbstractLogTarget::class)) {
        throw new \Exception(sprintf("%s is not sub-class of AbstractLogTarget", $targetClassName));
      }

      $targets[] = $targetClassName::create($target);
    }

    return new ResilientLogger(
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
   * @return string[]:
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
        $results[$entry->getId()] = $result;
      }

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

  /**
   * @template T
   * @param T $options
   * @param array $defaultOptions
   * @return T
   */
  private static function mergeOptions(array $options, array $defaultOptions): array {
    $merged = [];

    foreach ($defaultOptions as $key => $value) {
      if (array_key_exists($key, $options)) {
        $merged[$key] = $options[$key];
      } else {
        $merged[$key] = $value;
      }
    }

    return $merged;
  }
}