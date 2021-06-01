<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Heap\Node;

/**
 * Responsible for proper command chain generation.
 */
final class CommandGenerator
{
    public function generateStore(MapperInterface $mapper, object $entity, Node $node): ContextCarrierInterface
    {
        $state = $node->getState();

        if ($node->getStatus() === Node::NEW) {
            return $mapper->queueCreate($entity, $node, $state);
        }

        return $mapper->queueUpdate($entity, $node, $state);

        // // we can not use current command as [head, tail] update tuple
        // if (!$head instanceof InitCarrierInterface) {
        //     return $head;
        // }
        //
        // // in cases where we have to update new entity we can merge two commands into one
        // $split = new Split($head, $mapper->queueUpdate($entity, $node, $state));
        // $state->setCommand($split);
        //
        // return $split;
    }

    public function generateDelete(MapperInterface $mapper, object $entity, Node $node): CommandInterface
    {
        // currently we rely on db to delete all nested records (or soft deletes)
        return $mapper->queueDelete($entity, $node, $node->getState());
    }
}
