<?php

namespace ResilientLogger\Sources;

/**
 * @phpstan-type AuditLogEvent array{
 *   actor: array,
 *   date_time: \DateTimeInterface,
 *   operation: string,
 *   origin: string,
 *   target: array,
 *   environment: string,
 *   message: string,
 *   level?: int,
 *   extra?: array
 * }
 *
 * @phpstan-type AuditLogDocument array{
 *   "@timestamp": \DateTimeInterface,
 *   audit_event: AuditLogEvent
 * }
 */
final class Types {}
