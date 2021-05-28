<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

interface SameRowRelationInterface extends RelationInterface
{
    public function newQueue(Pool $pool, Tuple $tuple, $related, CommandInterface $command = null): void;
}
