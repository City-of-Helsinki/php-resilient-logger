<?php

declare(strict_types=1);

namespace ResilientLogger\Sources;

use \ResilientLogger\Sources\Types;

/**
 * @phpstan-import-type LogSourceConfig from Types
 */
interface AbstractLogSource {
  /**
   * Creates new log source entry if it's allowed.
   * 
   * @param int $level - Log level
   * @param mixed $message - Message
   * @param array $context - Extra context
   */
  function create(int $level, mixed $message, array $context = []): ?AbstractLogSourceEntry;

  /**
   * Returns all unsent entries, split to chunks of $chunkSize
   * 
   * @param int $chunkSize
   * @return \Generator<AbstractLogSourceEntry>
   **/
  function getUnsentEntries(int $chunkSize): \Generator;

  /**
   * Clears all sent entries that are older than $daysToKeep days.
   * 
   * @param int $daysToKeep
   **/
  function clearSentEntries(int $daysToKeep): void;
}
?>