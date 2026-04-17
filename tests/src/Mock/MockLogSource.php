<?php

namespace ResilientLogger\Tests\Mock;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Sources\AbstractLogSourceEntry;

class MockLogSourceEntry implements AbstractLogSourceEntry {
  private int $id;
  private int $level;
  private string $message;
  private array $context;
  private bool $sent;

  public function __construct(int $id, int $level, string $message, array $context, bool $sent) {
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
    $now = new \DateTime();
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
}

class MockLogSource extends AbstractLogSource {
  /** @var Array<MockLogSourceEntry> */
  public static array $entries = [];

  public function __construct() {
    parent::__construct(["environment" => "local", "origin" => "test"]);
  }

  public function create(int $level, mixed $message, array $context = []): AbstractLogSourceEntry {
    $id = random_int(0, 10000);
    $entry = new MockLogSourceEntry($id, $level, $message, $context, false);
    
    self::$entries[] = $entry;

    return $entry;
  }

  /** @return \Generator<AbstractLogSourceEntry> */
  public function getUnsentEntries(int $chunkSize): \Generator {
    $entries = array_filter(self::$entries, function(AbstractLogSourceEntry $entry) {
      return !$entry->isSent();
    });

    foreach(array_slice($entries, 0, $chunkSize) as $entry) {
      yield $entry;
    }
  }

  public function clearSentEntries(int $daysToKeep): void {
    /** Mock will be ignoring days to keep, it's only relevant on actual implementation. */
    self::$entries = array_filter(self::$entries, function(AbstractLogSourceEntry $entry) {
      return !$entry->isSent();
    });
  }
}

?>