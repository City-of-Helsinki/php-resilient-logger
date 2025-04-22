<?php

namespace ResilientLogger\Tests\Mock;

use ResilientLogger\Facade\AbstractLogFacade;

class MockLogFacade implements AbstractLogFacade {
  private int $id;
  private int $level;
  private string $message;
  private array $context;
  private bool $sent;

  /** @var Array<MockLogFacade> */
  public static array $entries = [];

  private function __construct(int $id, int $level, string $message, array $context, bool $sent) {
    $this->id = $id;
    $this->level = $level;
    $this->message = $message;
    $this->context = $context;
    $this->sent = $sent;
  }

  public function getId(): int {
    return $this->id;
  }

  public function getLevel(): int {
    return $this->level;
  }

  public function getMessage(): mixed {
    return $this->message;
  }

  public function getContext(): array {
    return $this->context;
  }

  public function isSent(): bool {
    return $this->sent;
  }

  public function markSent(): void {
    $this->sent = true;
  }

  public static function create(int $level, mixed $message, array $context = []): AbstractLogFacade {
    $id = random_int(0, 10000);
    $entry = new MockLogFacade($id, $level, $message, $context, false);
    
    self::$entries[] = $entry;

    return $entry;
  }

  /** @return \Generator<AbstractLogFacade> */
  public static function getUnsentEntries(int $chunkSize): \Generator {
    $entries = array_filter(self::$entries, function(AbstractLogFacade $entry) {
      return !$entry->isSent();
    });

    foreach(array_slice($entries, 0, $chunkSize) as $entry) {
      yield $entry;
    }
  }

  public static function clearSentEntries(int $daysToKeep): void {
    /** Mock will be ignoring days to keep, it's only relevant on actual implementation. */
    self::$entries = array_filter(self::$entries, function(AbstractLogFacade $entry) {
      return !$entry->isSent();
    });
  }
}

?>