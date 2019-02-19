<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Condition;
use Cycle\ORM\Command\Branch\ContextSequence;
use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Relation\Traits\PromiseOneTrait;

/**
 * Provides the ability to own and forward context values to child entity.
 */
class HasOneRelation extends AbstractRelation
{
    use PromiseOneTrait;

    /**
     * @inheritdoc
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface
    {
        if ($original instanceof PromiseInterface) {
            $original = $original->__resolve();
        }

        if ($related instanceof PromiseInterface) {
            $related = $related->__resolve();
        }

        if (is_null($related)) {
            if ($related === $original) {
                // no changes
                return new Nil();
            }

            if (!is_null($original)) {
                return $this->deleteOriginal($original);
            }
        }

        $resStore = $this->orm->queueStore($related);
        $relNode = $this->getNode($related, +1);
        $this->assertValid($relNode);

        // store command with mounted context paths
        $relStore = $this->forwardContext($parentNode, $this->innerKey, $resStore, $relNode, $this->outerKey);

        if (is_null($original)) {
            return $relStore;
        }

        $sequence = new ContextSequence();
        $sequence->addCommand($this->deleteOriginal($original));
        $sequence->addPrimary($relStore);

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
        $relNode = $this->getNode($original);

        // only delete original child when no other objects claim it
        return new Condition($this->orm->queueDelete($original), function () use ($relNode) {
            return !$relNode->getState()->hasClaims();
        });
    }
}