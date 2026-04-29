<?php

namespace ResilientLogger\Tests\Mock;

use ResilientLogger\Sources\AbstractLogSourceEntry;
use ResilientLogger\Targets\AbstractLogTarget;

class MockLogTarget implements AbstractLogTarget {
  private bool $result;
  private bool $required;

  /** @var Array<AbstractLogSourceEntry> */
  public array $entries = [];

  public function __construct(array $options) {
    $this->result = true;
    $this->required = $options["required"] ?? true;
  }
  
  public function submit(AbstractLogSourceEntry $entry): bool {
    if ($this->result) {
      $this->entries[] = $entry;
    }

    return $this->result;
  }

  public function isRequired(): bool {
    return $this->required;
  }

  public function setResult(bool $result) {
    $this->result = $result;
  }
}

?>