<?php

namespace ResilientLogger\Sources;

interface AbstractLogSource {
  public function getId(): int;
  public function getLevel(): int;
  public function getMessage(): mixed;
  public function getContext(): array;
  public function isSent(): bool;
  public function markSent(): void;
  public static function create(int $level, mixed $message, array $context = []): ?AbstractLogSource;

  /** @return \Generator<AbstractLogSource> */
  public static function getUnsentEntries(int $chunkSize): \Generator;
  public static function clearSentEntries(int $daysToKeep): void;
}
?>