<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;

class SoftDeletedMapper extends Mapper
{
    public function queueDelete($entity, Node $node, State $state): CommandInterface
    {
        $state->setData(['deleted_at' => new \DateTimeImmutable()]);

        $cmd = $this->queueUpdate($entity, $node, $state);

        $state->setStatus(Node::SCHEDULED_DELETE);
        $state->decClaim();

        return $cmd;
    }
}
