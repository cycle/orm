<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\ORMInterface;

interface CommandGeneratorInterface
{
    public function generateStoreCommand(ORMInterface $orm, Tuple $tuple): ?CommandInterface;

    public function generateDeleteCommand(ORMInterface $orm, Tuple $tuple): ?CommandInterface;
}
