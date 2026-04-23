<?php

declare(strict_types=1);

namespace ResilientLogger\Targets;

use ResilientLogger\Sources\AbstractLogSourceEntry;

interface AbstractLogTarget {
  function isRequired(): bool;
  function submit(AbstractLogSourceEntry $entry): bool;
}