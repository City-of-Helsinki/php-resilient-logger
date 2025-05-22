<?php

declare(strict_types=1);

namespace ResilientLogger\Handler;

use Monolog\LogRecord;
use Monolog\Handler\AbstractProcessingHandler;
use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Utils\Helpers;
use ResilientLogger\Exceptions\MissingContextException;

class ResilientLogHandler extends AbstractProcessingHandler {
  /**
   * @param class-string<AbstractLogSource> $logSource
   * @param array<string> $requiredFields
   */
  public function __construct(
    protected string $logSource,
    protected array $requiredFields = []
  ) {}

  /**
   * Used to store entry with ResilientLogger.
   * @param LogRecord $record Record to be stored with ResilientLogger
   * @throws MissingContextException
   */
  protected function write(LogRecord $record): void {
    $staticExtras = [
      'channel_name' => $record->channel,
      'record_time' => $record->datetime->format('U'),
    ];

    $extras = array_merge(
      $record->extra,
      $record->context,
      $staticExtras
    );

    Helpers::assertRequiredExtras(
      $extras,
      $this->requiredFields
    );

    $this->logSource::create(
      $record->level->toRFC5424Level(),
      $record->message,
      $extras
    );
  }
}

?>