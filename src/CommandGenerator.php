<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM;

use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\Branch\Split;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Command\InitCarrierInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\MapperInterface;
use Cycle\ORM\Promise\PromiseInterface;

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
        if ($entity instanceof PromiseInterface) {
            // we do not expect to store promises
            return new Nil();
        }

        $state = $node->getState();

        if ($node->getStatus() == Node::NEW) {
            $cmd = $mapper->queueCreate($entity, $node, $state);
            $state->setCommand($cmd);

            return $cmd;
        }

        $tail = $state->getCommand();
        if ($tail === null) {
            return $mapper->queueUpdate($entity, $node, $state);
        }

        // Command can aggregate multiple operations on soft basis.
        if (!$tail instanceof InitCarrierInterface) {
            return $tail;
        }

        // in cases where we have to update new entity we can merge two commands into one
        $split = new Split($tail, $mapper->queueUpdate($entity, $node, $state));
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