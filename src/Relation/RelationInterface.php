<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Base interface for all relations between entities
 *
 * @internal
 */
interface RelationInterface
{
    // Relation statuses in an unfinished transaction
    public const STATUS_PREPARE = 0;
    public const STATUS_PROCESS = 1;
    public const STATUS_DEFERRED = 2; // entity can be saved with resolved fields and updated with deferred fields later
    public const STATUS_RESOLVED = 3;

    public function getInnerKeys(): array;

    /**
     * Relation name.
     */
    public function getName(): string;

    /**
     * Target role.
     */
    public function getTarget(): string;

    /**
     * Must return true to trigger queue.
     */
    public function isCascade(): bool;

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void;

    public function queue(Pool $pool, Tuple $tuple): void;
}
