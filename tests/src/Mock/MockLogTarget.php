<?php

namespace ResilientLogger\Tests\Mock;

use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Targets\AbstractLogTarget;

class MockLogTarget extends AbstractLogTarget {
  private bool $result;

  /** @var Array<AbstractLogSourceEntry> */
  public static array $entries = [];

  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->setResult(true);
  }

  public function submit(AbstractLogSourceEntry $entry): bool {
    if ($this->result) {
      self::$entries[] = $entry;
    }

    return $this->result;
  }

  public function setResult(bool $result) {
    $this->result = $result;
  }
}

?>