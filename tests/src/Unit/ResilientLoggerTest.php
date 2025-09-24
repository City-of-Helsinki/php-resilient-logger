<?php

namespace ResilientLogger\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use ResilientLogger\ResilientLogger;
use ResilientLogger\Tests\Mock\MockLogSource;
use ResilientLogger\Tests\Mock\MockLogTarget;

#[CoversClass(ResilientLogger::class)]
class ResilientLoggerTest extends TestCase {
  private ResilientLogger $resilientLogger;

  public function setUp(): void {
    $options = [
      "sources" => [["class" => MockLogSource::class]],
      "targets" => [["class" => MockLogTarget::class]],
      "batch_limit" => 5000,
      "chunk_size" => 500,
      "store_old_entries_days" => 30,
    ];

    $this->resilientLogger = ResilientLogger::create($options);
    MockLogSource::$entries = [];
    MockLogTarget::$entries = [];
  }

  private function addEntries($numEntries): void {
    $numEntriesBefore = count(MockLogSource::$entries);

    for ($i = 0; $i < $numEntries; ++$i) {
      MockLogSource::create(0, "Hello", ["idx" => $i]);
    }

    $numEntriesAfter = $numEntriesBefore + $numEntries;
    $this->assertEquals(count(MockLogSource::$entries), $numEntriesAfter);
  }

  public function testSubmitUnsent() {
    $numEntries = 10;
    $this->addEntries($numEntries);
    $this->assertEquals($numEntries, count(MockLogSource::$entries));
    $this->assertEquals(0, count(MockLogTarget::$entries));

    $submitIds = $this->resilientLogger->submitUnsentEntries();
    $this->assertEquals(count($submitIds), $numEntries);
    $this->assertEquals($numEntries, count(MockLogTarget::$entries));
    $this->assertEquals(MockLogSource::$entries, MockLogTarget::$entries);
  }

  public function testClearSent() {
    $numEntries = 10;
    
    $this->addEntries($numEntries);
    $this->assertEquals($numEntries, count(MockLogSource::$entries));

    foreach (MockLogSource::$entries as $entry) {
      $entry->markSent();
    }

    $this->resilientLogger->clearSentEntries();
    $this->assertEquals(0, count(MockLogSource::$entries));
  }
}

?>