<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use JetBrains\PhpStorm\ExpectedValues;

/**
 * Manages single branch type between parent entity and other objects.
 */
interface RelationInterface
{
    public const STATUS_PREPARE = 0;
    public const STATUS_PROCESS = 1;
    public const STATUS_DEFERRED = 2;
    public const STATUS_RESOLVED = 3;

    public function getInnerKeys(): array;

    /**
     * Relation name.
     */
    public function getName(): string;

    /**
     * Target entity role.
     */
    public function getTarget(): string;

    /**
     * Must return true to trigger queue.
     */
    public function isCascade(): bool;

    /**
     * Init related entity value(s). Returns tuple [value, value to store as relation context]. If data null
     * relation must initiate empty relation state (when lazy loading is off).
     *
     * @param Node $node Parent node.
     *
     * @throws RelationException
     */
    public function init(Node $node, array $data): array;

    /**
     * Returns tuple of [promise to insert into entity, promise to store as relation context].
     *
     * @throws RelationException
     */
    public function initPromise(Node $node): array;

    /**
     * Extract the related values from the entity field value.
     *
     * @param mixed $value
     * @return mixed
     *
     * @throws RelationException
     */
    public function extract($value);

    /**
     * @param mixed $related
     */
    public function queue(Pool $pool, Tuple $tuple, $related): void;
}
