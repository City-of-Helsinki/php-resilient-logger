<?php

declare(strict_types=1);

namespace ResilientLogger\Sources;

use \ResilientLogger\Sources\Types;

/**
 * @phpstan-import-type LogSourceConfig from Types
 * @phpstan-import-type AuditLogDocument from Types
 */
interface AbstractLogSource {
  /**
   * Returns the ID attached to this entry.
   */
  public function getId(): int|string;

  /**
   * Returns the AuditLogDocument for given entry
   * 
   * @return AuditLogDocument
   **/
  public function getDocument(): array;

  /**
   * Returns the boolean representing if the entry is sent or not.
   */
  public function isSent(): bool;

  /**
   * Marks the entry as sent
   */
  public function markSent(): void;

  /**
   * Configures the source class.
   * 
   * @param LogSourceConfig $config
   */
  public static function configure(mixed $config): void;

  /**
   * Creates new log source entry if it's allowed.
   * 
   * @param int $level - Log level
   * @param mixed $message - Message
   * @param array $context - Extra context
   */
  public static function create(int $level, mixed $message, array $context = []): ?AbstractLogSource;

  /**
   * Returns all unsent entries, split to chunks of $chunkSize
   * 
   * @param int $chunkSize
   * @return \Generator<AbstractLogSource>
   **/
  public static function getUnsentEntries(int $chunkSize): \Generator;

  /**
   * Clears all sent entries that are older than $daysToKeep days.
   * 
   * @param int $daysToKeep
   **/
  public static function clearSentEntries(int $daysToKeep): void;
}
?>