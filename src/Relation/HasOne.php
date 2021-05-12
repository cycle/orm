<?php

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

    public function queue(CC $store, object $entity, Node $node, $related, $original): CommandInterface
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
            $this->innerKeys,
            $rStore,
            $rNode,
            $this->outerKeys
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
     */
    protected function deleteOriginal(object $original): CommandInterface
    {
        $rNode = $this->getNode($original);

        if ($this->isNullable()) {
            $store = $this->orm->queueStore($original);
            foreach ($this->outerKeys as $oKey) {
                $store->register($this->columnName($rNode, $oKey), null, true);
            }
            $rNode->getState()->decClaim();

            return new Condition($store, fn() => !$rNode->getState()->hasClaims());
        }

        // only delete original child when no other objects claim it
        return new Condition($this->orm->queueDelete($original), fn() => !$rNode->getState()->hasClaims());
    }
}
