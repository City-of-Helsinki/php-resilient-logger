<?php

namespace ResilientLogger\Handler;

use Monolog\LogRecord;
use Monolog\Handler\AbstractProcessingHandler;
use ResilientLogger\Submitter\AbstractSubmitter;

class ResilientLogHandler extends AbstractProcessingHandler {
  protected AbstractSubmitter $submitter;

  /**
   * @param AbstractSubmitter $submitter
   */
  public function __construct(AbstractSubmitter $submitter) {
    $this->submitter = $submitter;
  }

  protected function write(LogRecord $record): void {
    $extras = [
      'name' => $record->channel,
      'record_time' => $record->datetime->format('U'),
    ];

    $this->submitter->submit(
      $record->level->toRFC5424Level(),
      $record->message,
      array_merge($record->context, $extras)
    );
  }
}

?>