<?php

declare(strict_types=1);

namespace ResilientLogger\Exceptions;

/**
 * Runtime exception that is thrown when ResilientLogger is used to log
 * entry without configured required extra fields.
 */
class MissingContextException extends \RuntimeException {
  /**
   * @param array<string> $missingFields
   */
  public function __construct(protected array $missingFields) {
    parent::__construct(self::createMessage($missingFields));
  }

  public function getMissingFields() {
    return $this->missingFields;
  }

  private static function createMessage(array $missingFields): string {
    $entries = implode(",", $missingFields);
    $msg = "Log entry is missing required context entries: [{$entries}]";
    return $msg;
  }
}

?>
