<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\Branch\Split;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Command\InitCarrierInterface;
use Cycle\ORM\Heap\Node;

/**
 * Responsible for proper command chain generation.
 */
final class CommandGenerator
{
    /**
     * @inheritdoc
     */
    public function generateStore(MapperInterface $mapper, $entity, Node $node): ContextCarrierInterface
    {
        $state = $node->getState();

        if ($node->getStatus() == Node::NEW) {
            $cmd = $mapper->queueCreate($entity, $node, $state);
            $state->setCommand($cmd);

            return $cmd;
        }

        $head = $state->getCommand();
        if ($head === null) {
            return $mapper->queueUpdate($entity, $node, $state);
        }

        // we can not use current command as [head, tail] update tuple
        if (!$head instanceof InitCarrierInterface) {
            return $head;
        }

        // in cases where we have to update new entity we can merge two commands into one
        $split = new Split($head, $mapper->queueUpdate($entity, $node, $state));
        $state->setCommand($split);

        return $split;
    }

    /**
     * @inheritdoc
     */
    public function generateDelete(MapperInterface $mapper, $entity, Node $node): CommandInterface
    {
        // currently we rely on db to delete all nested records (or soft deletes)
        return $mapper->queueDelete($entity, $node, $node->getState());
    }
}
