<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use JetBrains\PhpStorm\ExpectedValues;

/**
 * @internal
 */
final class Tuple
{
    public const TASK_STORE = 0;
    public const TASK_DELETE = 1;
    public const TASK_FORCE_DELETE = 2;

    public const STATUS_PREPARING = 0;
    public const STATUS_WAITING = 1;
    public const STATUS_WAITED = 2;
    public const STATUS_DEFERRED = 3;
    public const STATUS_PROPOSED = 4;
    public const STATUS_PREPROCESSED = 5;
    public const STATUS_PROCESSED = 6;
    public const STATUS_UNPROCESSED = 7;

    public Node $node;
    public State $state;
    public MapperInterface $mapper;
    /**
     * `Null` in case when Entity persisted not deferred. Else cloned State object.
     */
    public ?State $persist = null;

    public function __construct(
        #[ExpectedValues(values: [self::TASK_STORE, self::TASK_DELETE, self::TASK_FORCE_DELETE])]
        public int $task,
        public object $entity,
        public bool $cascade,
        #[ExpectedValues(values: [
            self::STATUS_PREPARING,
            self::STATUS_WAITING,
            self::STATUS_WAITED,
            self::STATUS_DEFERRED,
            self::STATUS_PROPOSED,
            self::STATUS_PREPROCESSED,
            self::STATUS_PROCESSED,
            self::STATUS_UNPROCESSED,
        ])]
        public int $status,
    ) {
    }
}
