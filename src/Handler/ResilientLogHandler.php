<?php

declare(strict_types=1);

namespace ResilientLogger\Handler;

use Monolog\LogRecord;
use Monolog\Handler\AbstractProcessingHandler;
use ResilientLogger\Sources\AbstractLogSource;

class ResilientLogHandler extends AbstractProcessingHandler {
  /**
   * @param class-string<AbstractLogSource> $logSource
   */
  public function __construct(protected string $logSource) {}

  protected function write(LogRecord $record): void {
    $extras = [
      'name' => $record->channel,
      'record_time' => $record->datetime->format('U'),
    ];

    $this->logSource::create(
      $record->level->toRFC5424Level(),
      $record->message,
      array_merge($record->extra, $record->context, $extras)
    );
  }
}

?>