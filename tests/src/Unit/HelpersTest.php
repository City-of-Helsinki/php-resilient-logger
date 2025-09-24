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