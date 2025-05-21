<?php
declare(strict_types=1);

namespace MonkeysLegion\Events\Event;

final readonly class MigrationEvent
{
    public const START    = 'start';
    public const COMPLETE = 'complete';

    public function __construct(
        public string $migrationName,
        public string $stage               // self::START | self::COMPLETE
    ) {}
}