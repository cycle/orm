<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests\Fixtures;

use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\Database\Update;
use Spiral\Cycle\Context\ConsumerInterface;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Heap\State;
use Spiral\Cycle\Mapper\Mapper;

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