<?php

namespace ResilientLogger\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use ResilientLogger\Utils\Helpers;

#[CoversClass(Helpers::class)]
class HelpersTest extends TestCase {
  public function testValueAsArray() {
    $asStr = "hello";
    $asArray = ["value" => $asStr];

    $this->assertEquals($asArray, Helpers::valueAsArray($asStr));
    $this->assertEquals($asArray, Helpers::valueAsArray($asArray));
  }

  public function testSerializeDocument() {
    $date = new \DateTimeImmutable("2025-09-07T10:30:05.000+03:00");

    $document = Helpers::serializeDocument([
      "@timestamp" => $date,
      "audit_event" => [
        "date_time" => new \DateTime("2025-09-07T07:30:05.000+00:00"),
        "message" => "already a string",
        "level" => 0,
        "extra" => ["nested" => ["deep" => $date]],
      ],
    ]);

    // Dates are normalized to UTC, at any depth.
    $this->assertSame("2025-09-07T07:30:05.000Z", $document["@timestamp"]);
    $this->assertSame("2025-09-07T07:30:05.000Z", $document["audit_event"]["date_time"]);
    $this->assertSame("2025-09-07T07:30:05.000Z", $document["audit_event"]["extra"]["nested"]["deep"]);
  }

  public function testSerializeDocumentDoesNotMutateInput() {
    $date = new \DateTime("2025-09-07T10:30:05.000+03:00");
    $original = clone $date;

    Helpers::serializeDocument(["@timestamp" => $date]);

    // \DateTime::setTimezone() mutates in place, the caller's object must not.
    $this->assertEquals($original, $date);
  }

  public function testContentHash() {
    $a = [
      "a" => "b",
      "c" => "d"
    ];

    $b = [
      "c" => "d",
      "a" => "b"
    ];

    $this->assertEquals(Helpers::contentHash($a), Helpers::contentHash($b));
  }

  public function testMergeOptions() {
    $defaults = ["key1" => "fallback1", "key2" => "fallback2"];

    $options1 = ["key1" => "value1"];
    $merged1 = Helpers::mergeOptions($options1, $defaults);
    
    $this->assertEquals("value1", $merged1["key1"]);
    $this->assertEquals("fallback2", $merged1["key2"]);
    
    $options2 = ["key2" => "value2"];
    $merged2 = Helpers::mergeOptions($options2, $defaults);
    
    $this->assertEquals("fallback1", $merged2["key1"]);
    $this->assertEquals("value2", $merged2["key2"]);

    $options3 = ["key1" => "value1", "key2" => "value2"];
    $merged3 = Helpers::mergeOptions($options3, $defaults);

    $this->assertEquals("value1", $merged3["key1"]);
    $this->assertEquals("value2", $merged3["key2"]);
  }
}