<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Control\Nil;
use Spiral\ORM\DependencyInterface;
use Spiral\ORM\Exception\Relation\NullException;
use Spiral\ORM\Promise\Promise;
use Spiral\ORM\Selector;
use Spiral\ORM\State;
use Spiral\ORM\StateInterface;

class BelongsToRelation extends AbstractRelation implements DependencyInterface
{
    // todo: class
    public function initPromise(State $state, $data)
    {
        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return null;
        }

        if ($this->orm->getHeap()->hasPath("{$this->class}:$innerKey")) {
            // todo: has it!
            return $this->orm->getHeap()->getPath("{$this->class}:$innerKey");
        }

        return new Promise(
            [$this->outerKey => $innerKey]
            , function () use ($innerKey) {
            // todo: check in map
            if ($this->orm->getHeap()->hasPath("{$this->class}:$innerKey")) {
                // todo: improve it?
                return $this->orm->getHeap()->getPath("{$this->class}:$innerKey");
            }

            $selector = new Selector($this->orm, $this->class);
            $selector->where([$this->outerKey => $innerKey]);

            return $selector->fetchOne();
        });
    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $parent,
        $entity,
        StateInterface $state,
        $related,
        $original
    ): CommandInterface {
        if (is_null($related)) {
            if ($this->isRequired()) {
                throw new NullException("Relation {$this} can not be null");
            }

            if (!is_null($original)) {
                $parent->setContext($this->innerKey, null);
            }

            return new Nil();
        }

        $relStore = $this->orm->queueStore($related);
        $relState = $this->getState($related);
        $relState->addReference();

        $this->promiseContext($parent, $relState, $this->outerKey, $state, $this->innerKey);

        // todo: morph key

        return $relStore;
    }
}