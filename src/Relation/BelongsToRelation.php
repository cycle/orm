<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCommandInterface;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\Exception\Relation\NullException;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

// do not throw save?
class BelongsToRelation extends AbstractRelation
{
    const LEADING = true;

    // todo: move to the strategy
    public function queueChange(
        $parent,
        State $state,
        $related,
        ContextCommandInterface $command
    ): CommandInterface {
        if ($related === null && !$this->define(Relation::NULLABLE)) {
            throw new NullException(
                "Relation `{$this->class}`.`{$this->relation}` can not be null"
            );
        }

        $state->setRelation($this->relation, $related);

        if (!is_null($related)) {
            $inner = $this->orm->getMapper($related)->queueStore($related);

            $innerState = $this->orm->getHeap()->get($related);

            //dump($innerState);
            if (!empty($innerState->getData()[$this->schema[Relation::OUTER_KEY]])) {
                $command->setContext(
                    $this->schema[Relation::INNER_KEY],
                    $innerState->getData()[$this->schema[Relation::OUTER_KEY]]
                );
            } else {
                $innerState->onUpdate(function (State $state) use ($command) {
                    $command->setContext(
                        $this->schema[Relation::INNER_KEY],
                        $state->getData()[$this->schema[Relation::OUTER_KEY]]
                    );
                });
            }
        } else {
            $command->setContext($this->schema[Relation::INNER_KEY], null);

            return new NullCommand();
        }

        return $inner;
    }
}