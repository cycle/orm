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
use Spiral\ORM\Command\Database\Insert;
use Spiral\ORM\Command\Database\Update;
use Spiral\ORM\DependencyInterface;
use Spiral\ORM\Schema;
use Spiral\ORM\State;
use Spiral\ORM\Util\Promise;

/**
 * Variation of belongs-to relation which provides the ability to be nullable. Relation can be used
 * to create cyclic references. Relation does not trigger store operation of referenced object!
 *
 * @todo merge with belongs to (?)
 */
class RefersToRelation extends AbstractRelation implements DependencyInterface
{
    // todo: class
    public function initPromise(State $state, $data): array
    {
        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return [null, null];
        }

        // todo: search in map (?)

        if ($this->orm->getHeap()->hasPath("{$this->class}:$innerKey")) {
            // todo: has it!
            $i = $this->orm->getHeap()->getPath("{$this->class}:$innerKey");
            return [$i, $i];
        }

        $pr = new Promise(
            [$this->outerKey => $innerKey]
            , function () use ($innerKey) {
            // todo: check in map
            if ($this->orm->getHeap()->hasPath("{$this->class}:$innerKey")) {
                // todo: improve it?
                return $this->orm->getHeap()->getPath("{$this->class}:$innerKey");
            }

            // todo: this is critical to have right
            return $this->orm->getMapper($this->class)->getRepository()->findOne([$this->outerKey => $innerKey]);
        });

        return [$pr, $pr];
    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $parent,
        $entity,
        State $state,
        $related,
        $original
    ): CommandInterface {
        // refers-to relation is always nullable (as opposite to belongs-to)
        if (is_null($related)) {
            if (!is_null($original)) {
                $parent->setContext($this->innerKey, null);
            }

            return new Nil();
        }

        $relState = $this->getState($related);

        // related object exists, we can update key immediately
        if (!empty($outerKey = $this->fetchKey($relState, $this->outerKey))) {
            if ($outerKey != $this->fetchKey($state, $this->innerKey)) {
                $parent->setContext($this->innerKey, $outerKey);
            }

            return new Nil();
        }

        // this needs to be better

        // todo: use queue store? merge with belongs to?

        $relState = $this->getState($related);

        /*
         * REMEMBER THE CYCLES!!!!
         */


        if (!empty($relState->getLeadCommand())) {
            $update = $relState->getLeadCommand();

            // todo: how reliable is it? it's not
            if (!($update instanceof Insert)) {
                $this->promiseContext($update, $relState, $this->outerKey, $state, $this->innerKey);
                return new Nil();
            }
        }

        // why am i taking same command?
        $update = new Update(
            $this->orm->getDatabase($entity),
            $this->orm->getSchema()->define(get_class($entity), Schema::TABLE)
        );

        // todo: here we go, the problem is that i need UPDATE command to be automatically
        // created here, or we are going to end in a infinite loop OR inability to resolve the command
        // $update = $this->orm->queueStore($entity);

        // this will give UPDATE (!)

        $primaryKey = $this->orm->getSchema()->define(get_class($entity), Schema::PRIMARY_KEY);
        $this->promiseScope($update, $state, $primaryKey, $this->getState($related), $primaryKey);
        $this->promiseContext($update, $this->getState($related), $this->outerKey, $state, $this->innerKey);

        return $update;
    }
}