<?php

declare(strict_types=1);

namespace ResilientLogger\Targets;

use ResilientLogger\Sources\AbstractLogSourceEntry;

abstract class AbstractLogTarget {
  protected bool $required;

  protected function __construct(array $options) {
    $this->required = array_key_exists("required", $options)
      ? $options["required"]
      : true;
  }

  public function isRequired(): bool {
    return $this->required;
  }

  public abstract function submit(AbstractLogSourceEntry $entry): bool;
}