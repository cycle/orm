<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

interface SameRowRelationInterface extends RelationInterface
{
    public function queue(Pool $pool, Tuple $tuple, $related, StoreCommandInterface $command = null): void;
}
