<?php

namespace ResilientLogger\Submitter;

use ResilientLogger\Facade\AbstractLogFacade;

abstract class AbstractSubmitter {
    /*
     * Abstract base class for different submitters. By design, does not use any implementation specific
     * storages or submit targets. Those are defined by sub-classes and runtime provided log_facade class.
     */

    private const DEFAULT_OPTIONS = [
        'log_facade' => 'Path\To\LogFacade',
        'store_old_entries_days' => 30,
        'batch_limit' => 5000,
        'chunk_size' => 500,
        'next_submit' => '+15min',
        'next_clear' => 'first day of next month midnight',
    ];

    /** @var ?class-string<AbstractLogFacade> $logFacade */
    private string $logFacade;
    private int $batchLimit;
    private int $chunkSize;
    private int $storeOldEntriesDays;
    private ?string $nextSubmitAt;
    private ?string $nextClearAt;

    /**
     * @param array{
     *   log_facade: class-string<AbstractLogFacade>,
     *   store_old_entries_days: int,
     *   batch_limit: int,
     *   chunk_size: int,
     *   next_submit_at: string,
     *   next_clear_at: string,
     * } $options
     */
    public function __construct(array $options) {
        $options = self::mergeOptions(self::DEFAULT_OPTIONS, $options);
        $this->logFacade = $options["log_facade"];
        $this->storeOldEntriesDays = $options["store_old_entries_days"];
        $this->batchLimit = $options["batch_limit"];
        $this->chunkSize = $options["chunk_size"];
        $this->nextSubmitAt = $options["next_submit"];
        $this->nextClearAt = $options["next_clear"];

        if (!is_subclass_of($this->logFacade, AbstractLogFacade::class)) {
            throw new \Exception(sprintf("%s is not sub-class of AbstractLogFacade", $this->logFacade));
        }
    }

    /* This method is different for each submitter, so it's required to override this. */
    protected abstract function _submitEntry(AbstractLogFacade $entry): ?string;
    
    protected function submitEntry(AbstractLogFacade $entry): ?string {
        $result_id = $this->_submitEntry($entry);

        if ($result_id != null) {
            $entry->markSent();
            return $result_id;
        }
        
        return null;
    }

    public function submit(int $level, mixed $message, array $context = []): ?string {
        $entry = $this->logFacade::create($level, $message, $context);
        return $this->submitEntry($entry);
    }

    /**
     * @return string[]:
     */
    public function submitUnsentEntries(): array {
        /** @var string[] $result_ids */
        $result_ids = [];
        $count = 0;

        foreach ($this->getUnsentEntries() as $entry) {
            if ($count >= $this->batchLimit) {
                break;
            }

            $result_id = $this->submitEntry($entry);

            if ($result_id != null) {
                $entry->markSent();
                array_push($result_ids, $result_id);
            }

            $count++;
        }

        return $result_ids;
    }

    /**
     * @return \Generator<AbstractLogFacade>
     */
    public function getUnsentEntries(): \Generator {
        return $this->logFacade::getUnsentEntries($this->chunkSize);
    }
    
    public function clearSentEntries(): void {
        $this->logFacade::clearSentEntries($this->storeOldEntriesDays);
    }

    public function getNextSubmitUnsentAt(int $now): int|false {
        if ($this->nextSubmitAt == null) {
            return false;
        }

        return strtotime($this->nextSubmitAt, $now);
    }

    public function getNextClearSentAt(int $now): int|false {
        if ($this->nextClearAt == null) {
            return false;
        }

        return strtotime($this->nextClearAt, $now);
    }

    private static function mergeOptions(array $default, array $override): array {
        $merged = [];

        foreach ($default as $key => $value) {
            if (array_key_exists($key, $override)) {
                $merged[$key] = $override[$key];
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}