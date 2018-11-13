<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CommandPromiseInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

class BelongsToRelation extends AbstractRelation
{
    const LEADING = true;

    // todo: move to the strategy
    public function queueChange(
        $parent,
        State $state,
        CommandPromiseInterface $command
    ): CommandInterface {

        $related = $this->getRelated($parent);
        //  $orig = $state->getRelation($this->relation);

        $state->setRelation($this->relation, $related);

        //   $relState = $this->orm->getHeap()->get($related);
        //            if (!empty($relState)) {
        //                $relState->addReference();
        //                if ($relState->getRefCount() > 2) {
        //                    // todo: detect if it's the same parent over and over again?
        //                    return new NullCommand();
        //                }
        //            }

        // todo: dirty state [?]
        $inner = $this->orm->getMapper($related)->queueStore($related);

        $inner->onExecute(function (CommandPromiseInterface $inner) use ($command, $related) {
            $command->addContext(
                $this->schema[Relation::INNER_KEY],
                $this->lookupKey($this->schema[Relation::OUTER_KEY], $related, $inner)
            );
        });

        return $inner;
    }
}