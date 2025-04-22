<?php

namespace ResilientLogger\Tests\Mock;

use ResilientLogger\Facade\AbstractLogFacade;
use ResilientLogger\Submitter\AbstractSubmitter;

class MockSubmitter extends AbstractSubmitter {
  private ?AbstractLogFacade $entry;
  private ?string $resultId;

  public function __construct(array $options) {
    parent::__construct($options);
    $this->setResultId("OK");
  }

  protected function _submitEntry(AbstractLogFacade $entry): ?string {
    $this->entry = $entry;
    return $this->resultId;
  }

  public function getEntry(): AbstractLogFacade {
    return $this->entry;
  }

  public function clearEntry(): void {
    $this->entry = null;
  }

  public function setResultId(?string $resultId) {
    $this->resultId = $resultId;
  }
}

?>