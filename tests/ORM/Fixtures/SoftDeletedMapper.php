<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;

class SoftDeletedMapper extends Mapper
{
    public function queueDelete($entity, Node $node, State $state): CommandInterface
    {
        $state->setStatus(Node::SCHEDULED_DELETE);
        $state->decClaim();

        $cmd = new Update(
            $this->source->getDatabase(),
            $this->source->getTable(),
            ['deleted_at' => new \DateTimeImmutable()]
        );

        $cmd->waitScope($this->primaryColumn);

        $state->forward(
            $this->primaryKey,
            $cmd,
            $this->primaryColumn,
            true,
            ConsumerInterface::SCOPE
        );

        return $cmd;
    }
}
