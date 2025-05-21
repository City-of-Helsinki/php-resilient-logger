<?php

declare(strict_types=1);

namespace ResilientLogger\Utils;

use Monolog\Level;
use ResilientLogger\Sources\AbstractLogSource;

class Helpers {
  static function createTargetDocument(
    AbstractLogSource $entry,
    Level $fallbackLevel = Level::Info
  ): array {
    $message = $entry->getMessage();
    $document = $entry->getContext() ?? [];
    $logLevel = $entry->getLevel() ?? $fallbackLevel;

    $document["entry_id"] = $entry->getId();

    if ($message != null) {
      $document["log_message"] = $message;
    }

    if ($logLevel != null) {
      $document["log_level"] = $logLevel;
    }

    return $document;
  }

  static function createDeepSortedCopy(mixed $data): mixed {
    if (is_object($data)) {
      $data = (array) $data;
    }

    if (is_array($data)) {
      $sorted = [];

      foreach ($data as $key => $value) {
        $sorted[$key] = self::createDeepSortedCopy($value);
      }

      ksort($sorted);
      return $sorted;
    }

    return $data;
  }

  static function contentHash(mixed $contents): string {
    $stringified = json_encode(self::createDeepSortedCopy($contents));
    return hash('sha256', $stringified);
  }
}

?>
