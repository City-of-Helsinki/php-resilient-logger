<?php

declare(strict_types=1);

namespace ResilientLogger\Sources;

use \ResilientLogger\Sources\Types;

/**
 * @phpstan-import-type AuditLogDocument from Types
 */
interface AbstractLogSourceEntry {
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
}
?>