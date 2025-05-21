<?php

declare(strict_types=1);

namespace ResilientLogger\Targets;

use ResilientLogger\Sources\AbstractLogSource;

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

  public static function create(array $options): AbstractLogTarget {
    return new static($options);
  }

  public abstract function submit(AbstractLogSource $entry): bool;
}