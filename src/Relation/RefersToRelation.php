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
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\Exception\Relation\NullException;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

class RefersToRelation extends AbstractRelation
{
    const LEADING = false;

    // todo: move to the strategy
    public function queueChange(
        $parent,
        State $state,
        CommandPromiseInterface $command
    ): CommandInterface {
        $related = $this->getRelated($parent);
        $orig = $state->getRelation($this->relation);

        if ($related === null && !$this->define(Relation::NULLABLE)) {
            throw new NullException(
                "Relation `{$this->class}`.`{$this->relation}` can not be null"
            );
        }

        // todo: super interesting use-case
        // if related object is new,
        // issue related command to be executed after parent command
        // and after issued command ?
        // but issued command can be issued after this one?

        //$cmd = new UpdateCommand();

        // todo: depends on related state?
        $relState = $this->orm->getHeap()->get($related);
        dump($relState);

        if (is_null($relState)) {
            $command->onExecute(function () use ($related) {
                $relState = $this->orm->getHeap()->get($related);
                $relState->getActiveCommand()->onExecute(function (CommandPromiseInterface $c) {
                    // todo: activate command
                    dump('READY TO ACTIVATE');
                    dump($c->getPrimaryKey());

                });
            });
        } else {
            dump('READY TO ACTIVATE (RELSTATE IS READY)');

            // todo: set immediately?
            // todo: on resolved reference
        }

        // TODO: comment depends on user, user does not depends on comment ?
        // todo: reset ?

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

        return new NullCommand();
    }
}