<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use JetBrains\PhpStorm\ExpectedValues;

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

    #[ExpectedValues(values: [self::TASK_STORE, self::TASK_DELETE, self::TASK_FORCE_DELETE])]
    public int $task;

    #[ExpectedValues(values: [self::STATUS_PREPARING, self::STATUS_WAITING, self::STATUS_DEFERRED, self::STATUS_PROCESSED])]
    public int $status;

    public object $entity;

    public bool $cascade;

    public ?Node $node;

    public ?State $state;

    public ?MapperInterface $mapper;

    public function __construct(
        #[ExpectedValues(values: [self::TASK_STORE, self::TASK_DELETE, self::TASK_FORCE_DELETE])]
        int $task,
        object $entity,
        bool $cascade,
        ?Node $node,
        ?State $state,
        #[ExpectedValues(values: [self::STATUS_PREPARING, self::STATUS_WAITING, self::STATUS_DEFERRED, self::STATUS_PROCESSED])]
        int $status = self::STATUS_PREPARING
    ) {
        $this->cascade = $cascade;
        $this->entity = $entity;
        $this->node = $node;
        $this->state = $state;
        $this->task = $task;
        $this->status = $status;
    }
}
