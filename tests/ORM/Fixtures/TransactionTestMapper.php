<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;

class TransactionTestMapper extends Mapper
{
    public function queueDelete($entity, Node $node, State $state): CommandInterface
    {
        if ($entity->id == '3') {
            return new class($this->source->getDatabase()) implements CommandInterface {

                public function __construct(private DatabaseInterface $database)
                {
                }

                public function isReady(): bool
                {
                    return true;
                }

                public function isExecuted(): bool
                {
                    return false;
                }

                public function execute(): void
                {
                    throw new \Exception('Something went wrong');
                }

                public function getDatabase(): ?DatabaseInterface
                {
                    return $this->database;
                }
            };
        }

        return parent::queueDelete($entity, $node, $state);
    }
}
