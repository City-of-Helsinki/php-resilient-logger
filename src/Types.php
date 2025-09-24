<?php

namespace ResilientLogger;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Targets\AbstractLogTarget;

/**
 * @phpstan-type ResilientLoggerOptions array{
 *   sources: array<
 *     array{
 *       class: class-string<AbstractLogSource>,
 *     }
 *   >,
 *   targets: array<
 *     array{
 *       class: class-string<AbstractLogTarget>,
 *     }
 *   >,
 *   store_old_entries_days: int,
 *   batch_limit: int,
 *   chunk_size: int,
 * }
 */
final class Types {}
