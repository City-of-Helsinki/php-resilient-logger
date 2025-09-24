<?php

namespace ResilientLogger\Tests\Mock;

use DateTime;
use ResilientLogger\Sources\AbstractLogSource;

class MockLogSource implements AbstractLogSource {
  private int $id;
  private int $level;
  private string $message;
  private array $context;
  private bool $sent;

  /** @var Array<MockLogSource> */
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

  public function getDocument(): array {
    $now = new DateTime();
    return [
      "@timestamp" => $now,
      "audit_event" => [
        "actor" => ["value" => "dummy_actor"],
        "date_time" => $now,
        "operation" => "test_operation",
        "origin" => "test_origin",
        "target" => ["value" => "test_target"],
        "environment" => "test_environment",
        "message" => $this->message,
        "level" => $this->level,
        "extra" => $this->context,
      ]
    ];
  }

  public function isSent(): bool {
    return $this->sent;
  }

  public function markSent(): void {
    $this->sent = true;
  }

  public static function create(int $level, mixed $message, array $context = []): AbstractLogSource {
    $id = random_int(0, 10000);
    $entry = new MockLogSource($id, $level, $message, $context, false);
    
    self::$entries[] = $entry;

    return $entry;
  }

  /** @return \Generator<AbstractLogSource> */
  public static function getUnsentEntries(int $chunkSize): \Generator {
    $entries = array_filter(self::$entries, function(AbstractLogSource $entry) {
      return !$entry->isSent();
    });

    foreach(array_slice($entries, 0, $chunkSize) as $entry) {
      yield $entry;
    }
  }

  public static function clearSentEntries(int $daysToKeep): void {
    /** Mock will be ignoring days to keep, it's only relevant on actual implementation. */
    self::$entries = array_filter(self::$entries, function(AbstractLogSource $entry) {
      return !$entry->isSent();
    });
  }
}

?>