<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Condition;
use Cycle\ORM\Command\Branch\ContextSequence;
use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation\Traits\PromiseOneTrait;

/**
 * Provides the ability to own and forward context values to child entity.
 */
class HasOne extends AbstractRelation
{
    use PromiseOneTrait;

    /**
     * @inheritdoc
     */
    public function queue(CC $store, $entity, Node $node, $related, $original): CommandInterface
    {
        if ($original instanceof ReferenceInterface) {
            $original = $this->resolve($original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }

        if ($related === null) {
            if ($related === $original) {
                // no changes
                return new Nil();
            }

            if ($original !== null) {
                return $this->deleteOriginal($original);
            }
        }

        $rStore = $this->orm->queueStore($related);
        $rNode = $this->getNode($related, +1);
        $this->assertValid($rNode);

        // store command with mounted context paths
        $rStore = $this->forwardContext(
            $node,
            $this->innerKey,
            $rStore,
            $rNode,
            $this->outerKey
        );

        if ($original === null) {
            return $rStore;
        }

        $sequence = new ContextSequence();
        $sequence->addCommand($this->deleteOriginal($original));
        $sequence->addPrimary($rStore);

        return $sequence;
    }

    /**
     * Delete original related entity of no other objects reference to it.
     *
     * @param object $original
     * @return CommandInterface
     */
    protected function deleteOriginal($original): CommandInterface
    {
        $rNode = $this->getNode($original);

        if ($this->isNullable()) {
            $store = $this->orm->queueStore($original);
            $store->register($this->outerKey, null, true);
            $rNode->getState()->decClaim();

            return new Condition($store, function () use ($rNode) {
                return !$rNode->getState()->hasClaims();
            });
        }

        // only delete original child when no other objects claim it
        return new Condition($this->orm->queueDelete($original), function () use ($rNode) {
            return !$rNode->getState()->hasClaims();
        });
    }
}
