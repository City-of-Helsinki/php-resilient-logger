<?php

namespace ResilientLogger\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use ResilientLogger\Handler\ResilientLogHandler;

use Monolog\Logger;
use ResilientLogger\Tests\Mock\MockLogSource;

#[CoversClass(ResilientLogHandler::class)]
class ResilientLogHandlerTest extends TestCase {
  private Logger $logger;

  public function setUp(): void {
    $this->logger = new Logger('dummy_logger');
    $this->logger->pushHandler(new ResilientLogHandler(MockLogSource::class));
  }

  public function testSubmit() {
    $expectedMessage = "Hello World";
    $expectedContext = ["a" => "b", "c" => "d", "e" => "f"];

    $this->logger->info($expectedMessage, $expectedContext);
    $entry = MockLogSource::$entries[count(MockLogSource::$entries) - 1];

    $document = $entry->getDocument();
    $auditEvent = $document["audit_event"];
    $actualMessage = $auditEvent["message"];
    $actualContext = $auditEvent["extra"];

    $this->assertInstanceOf(\DateTimeInterface::class, $document["@timestamp"]);
    $this->assertEquals($expectedMessage, $actualMessage);

    foreach ($expectedContext as $key => $expectedValue) {
      $actualValue = $actualContext[$key];
      $this->assertEquals($expectedValue, $actualValue);
    }
  }
}

?>