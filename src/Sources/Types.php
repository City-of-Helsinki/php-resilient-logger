<?php

namespace ResilientLogger\Sources;

/**
 * @phpstan-type LogSourceConfig array{
 *   environment: string,
 *   origin: string,
 * }
 *
 * @phpstan-type AuditLogEvent array{
 *   actor: array<string, mixed>,
 *   date_time: \DateTimeInterface,
 *   operation: string,
 *   origin: string,
 *   target: array<string, mixed>,
 *   environment: string,
 *   message: string,
 *   level?: int,
 *   extra?: array<string, mixed>,
 * }
 *
 * @phpstan-type AuditLogDocument array{
 *   "@timestamp": \DateTimeInterface,
 *   audit_event: AuditLogEvent
 * }
 */
final class Types {}
