<?php

namespace ResilientLogger\Tests\Mock;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Targets\AbstractLogTarget;

class MockLogTarget extends AbstractLogTarget {
  private bool $result;

  /** @var Array<AbstractLogSource> */
  public static array $entries = [];

  protected function __construct(array $options = []) {
    parent::__construct($options);
    $this->setResult(true);
  }

  public function submit(AbstractLogSource $entry): bool {
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