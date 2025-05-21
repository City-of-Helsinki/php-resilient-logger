<?php

namespace ResilientLogger\Facade;

interface AbstractLogFacade {
  public function getId(): int;
  public function getLevel(): int;
  public function getMessage(): mixed;
  public function getContext(): array;
  public function isSent(): bool;
  public function markSent(): void;
  public static function create(int $level, mixed $message, array $context = []): AbstractLogFacade;

  /** @return \Generator<AbstractLogFacade> */
  public static function getUnsentEntries(int $chunkSize): \Generator;
  public static function clearSentEntries(int $daysToKeep): void;
}
?>