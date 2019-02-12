<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle;

use Spiral\Cycle\Command\Branch\Nil;
use Spiral\Cycle\Command\Branch\Split;
use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Command\InitCarrierInterface;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Mapper\MapperInterface;
use Spiral\Cycle\Promise\PromiseInterface;

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