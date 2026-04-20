<?php

namespace ResilientLogger\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use ResilientLogger\ResilientLogger;
use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Tests\Mock\MockLogSource;
use ResilientLogger\Tests\Mock\MockLogSourceFactory;
use ResilientLogger\Tests\Mock\MockLogTarget;

#[CoversClass(ResilientLogger::class)]
class ResilientLoggerTest extends TestCase {
  private ResilientLogger $resilientLogger;
  private MockLogSource $source;
  private MockLogTarget $target;

  public function setUp(): void {
    $options = [
      "sources" => [["class" => MockLogSource::class]],
      "targets" => [["class" => MockLogTarget::class]],
      "environment" => "test",
      "origin" => "test",
      "batch_limit" => 5000,
      "chunk_size" => 500,
      "store_old_entries_days" => 30,
    ];

    $this->resilientLogger = ResilientLogger::create($options);
    
    $source = $this->resilientLogger->getSources()[0];
    $target = $this->resilientLogger->getTargets()[0];

    $this->assertInstanceOf(MockLogSource::class, $source);
    $this->assertInstanceOf(MockLogTarget::class, $target);

    $this->source = $source;
    $this->target = $target;
  }

  private function addEntries($numEntries): void {
    $numEntriesBefore = count($this->source->entries);

    for ($i = 0; $i < $numEntries; ++$i) {
      $this->source->create(0, "Hello", ["idx" => $i]);
    }

    $numEntriesAfter = $numEntriesBefore + $numEntries;
    $this->assertEquals(count($this->source->entries), $numEntriesAfter);
  }

  public function testSubmitUnsent() {
    $numEntries = 10;
    $this->addEntries($numEntries);
    $this->assertEquals($numEntries, count($this->source->entries));
    $this->assertEquals(0, count($this->target->entries));

    $submitIds = $this->resilientLogger->submitUnsentEntries();
    $this->assertEquals(count($submitIds), $numEntries);

    $this->assertEquals($numEntries, count($this->source->entries));
    $this->assertEquals($this->source->entries, $this->target->entries);
  }

  public function testClearSent() {
    $numEntries = 10;
    
    $this->addEntries($numEntries);
    $this->assertEquals($numEntries, count($this->source->entries));

    foreach ($this->source->entries as $entry) {
      $entry->markSent();
    }

    $this->resilientLogger->clearSentEntries();
    $this->assertEquals(0, count($this->source->entries));
  }

  public function testFactoryPatterns() {
    $freeFunction = fn (array $config): AbstractLogSource => new MockLogSource($config);  
    $factoryInstance1 = new MockLogSourceFactory(["instanceValue" => 0]);
    $factoryInstance2 = new MockLogSourceFactory(["instanceValue" => 1]);

    $options = [
      "sources" => [
        ["factory" => $freeFunction, "type" => "function"],
        ["factory" => [MockLogSourceFactory::class, "staticMethod"], "type" => "staticMethod"],
        ["factory" => [$factoryInstance1, "instanceMethod"], "type" => "instanceMethod"],
        ["factory" => [$factoryInstance2, "instanceMethod"], "type" => "instanceMethod"],
      ],
      "targets" => [["class" => MockLogTarget::class]],
      "environment" => "test",
      "origin" => "test",
      "batch_limit" => 5000,
      "chunk_size" => 500,
      "store_old_entries_days" => 30,
    ];

    $resilientLogger = ResilientLogger::create($options);

    /** @var MockLogSource[] */
    $sources = $resilientLogger->getSources();

    $freeSource = $sources[0]->getConfig();
    $staticSource = $sources[1]->getConfig();
    $instanceSource1 = $sources[2]->getConfig();
    $instanceSource2 = $sources[3]->getConfig();

    $this->assertEquals("function", $freeSource["type"]);
    $this->assertEquals("staticMethod", $staticSource["type"]);
    $this->assertEquals("instanceMethod", $instanceSource1["type"]);
    $this->assertEquals("instanceMethod", $instanceSource2["type"]);

    $this->assertEquals(0, $instanceSource1["instanceValue"]);
    $this->assertEquals(1, $instanceSource2["instanceValue"]);
  }
}

?>
