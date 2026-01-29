<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * @internal
 */
interface SameRowRelationInterface extends ActiveRelationInterface
{
    public function queue(Pool $pool, Tuple $tuple, ?StoreCommandInterface $command = null): void;
}
