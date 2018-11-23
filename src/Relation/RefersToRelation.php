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
use Spiral\ORM\Command\Database\Update;
use Spiral\ORM\DependencyInterface;
use Spiral\ORM\Schema;
use Spiral\ORM\State;
use Spiral\ORM\StateInterface;

/**
 * Variation of belongs-to relation which provides the ability to be nullable. Relation can be used
 * to create cyclic references. Relation does not trigger store operation of referenced object!
 */
class RefersToRelation extends AbstractRelation implements DependencyInterface
{
    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $command,
        $entity,
        StateInterface $state,
        $related,
        $original
    ): CommandInterface {
        // refers-to relation is always nullable (as opposite to belongs-to)
        if (is_null($related)) {
            if (!is_null($original)) {
                $command->setContext($this->innerKey, null);
            }

            return new Nil();
        }

        $relState = $this->getState($related);

        // related object exists, we can update key immediately
        if (!empty($outerKey = $this->fetchKey($relState, $this->outerKey))) {
            if ($outerKey != $this->fetchKey($state, $this->innerKey)) {
                $command->setContext($this->innerKey, $outerKey);
            }

            return new Nil();
        }

        // this needs to be better

        // todo: use queue store?

        $update = new Update(
            $this->orm->getDatabase($entity),
            $this->orm->getSchema()->define(get_class($entity), Schema::TABLE)
        );

        $primaryKey = $this->orm->getSchema()->define(get_class($entity), Schema::PRIMARY_KEY);
        $this->promiseScope($update, $state, $primaryKey, null, $primaryKey);

        // state either not found or key value is not set, subscribe thought the heap
        $update->waitContext($this->innerKey, true);

        $this->orm->getHeap()->onChange($related, function (State $state) use ($update) {
            if (!empty($value = $this->fetchKey($state, $this->outerKey))) {
                $update->setContext($this->innerKey, $value);
                $update->freeContext($this->innerKey);
            }
        });

        // update state
        $update->onExecute(function (Update $command) use ($state) {
            $state->setData($command->getContext());
        });

        return $update;
    }
}