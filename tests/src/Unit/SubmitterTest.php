<?php

namespace ResilientLogger\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use ResilientLogger\Tests\Mock\MockSubmitter;
use ResilientLogger\Handler\ResilientLogHandler;
use ResilientLogger\Submitter\AbstractSubmitter;

use Monolog\Handler\Handler;
use Monolog\Logger;
use ResilientLogger\Tests\Mock\MockLogFacade;

#[CoversClass(AbstractSubmitter::class)]
class SubmitterTest extends TestCase {
  private MockSubmitter $submitter;
  private Handler $handler;
  private Logger $logger;

  public function setUp(): void {
    $this->submitter = new MockSubmitter([
      'log_facade' => 'ResilientLogger\Tests\Mock\MockLogFacade',
    ]);
    $this->handler = new ResilientLogHandler($this->submitter);
    $this->logger = new Logger('dummy_logger');
    $this->logger->pushHandler($this->handler);
    
    MockLogFacade::$entries = [];
  }

  private function addEntries($numEntries): void {
    $numEntriesBefore = count(MockLogFacade::$entries);

    for ($i = 0; $i < $numEntries; ++$i) {
      $this->logger->info("Message {$i}");
    }

    $numEntriesAfter = $numEntriesBefore + $numEntries;
    $this->assertEquals(count(MockLogFacade::$entries), $numEntriesAfter);
  }

  public function testSubmit() {
    $expectedMessage = "Hello World";
    $expectedContext = ["a" => "b", "c" => "d"];

    $this->logger->info($expectedMessage, $expectedContext);
    $entry = $this->submitter->getEntry();

    $actualMessage = $entry->getMessage();
    $actualContext = $entry->getContext();

    $this->assertEquals($expectedMessage, $actualMessage);

    foreach ($expectedContext as $key => $expectedValue) {
      $actualValue = $actualContext[$key];
      $this->assertEquals($expectedValue, $actualValue);
    }
  }

  public function testSubmitUnsent() {
    $numEntries = 10;
    $this->submitter->setResultId(null);
    $this->addEntries($numEntries);
    $this->submitter->setResultId("OK");

    $submitIds = $this->submitter->submitUnsentEntries();
    $this->assertEquals(count($submitIds), $numEntries);
  }

  public function testClearSent() {
    $numEntries = 10;
    
    $this->addEntries($numEntries);
    $this->assertEquals(count(MockLogFacade::$entries), $numEntries);

    $this->submitter->clearSentEntries();
    $this->assertEquals(count(MockLogFacade::$entries), 0);
  }
}

?>