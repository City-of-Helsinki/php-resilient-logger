<?php

declare(strict_types=1);

namespace ResilientLogger\Sources;

use \ResilientLogger\Sources\Types;

/**
 * @phpstan-import-type LogSourceConfig from Types
 */
abstract class AbstractLogSource {
  /**
   * @param LogSourceConfig $config
   */
  public function __construct(protected array $config) {}

  /**
   * Creates new log source entry if it's allowed.
   * 
   * @param int $level - Log level
   * @param mixed $message - Message
   * @param array $context - Extra context
   */
  abstract public function create(int $level, mixed $message, array $context = []): ?AbstractLogSourceEntry;

  /**
   * Returns all unsent entries, split to chunks of $chunkSize
   * 
   * @param int $chunkSize
   * @return \Generator<AbstractLogSourceEntry>
   **/
  abstract public function getUnsentEntries(int $chunkSize): \Generator;

  /**
   * Clears all sent entries that are older than $daysToKeep days.
   * 
   * @param int $daysToKeep
   **/
  abstract public function clearSentEntries(int $daysToKeep): void;
}
?>