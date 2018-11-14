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
use Spiral\ORM\Command\Database\LinkCommand;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\Exception\Relation\NullException;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
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

        $state->setRelation($this->relation, $related);

        if (is_null($related)) {
            // todo: reset value
            return new NullCommand();
        }

        $link = new LinkCommand(
            $this->orm->getDatabase($parent),
            $this->orm->getSchema()->define(get_class($parent), Schema::TABLE)
        );

        $pk = $this->orm->getSchema()->define(get_class($parent), Schema::PRIMARY_KEY);

        $command->onExecute(function (CommandPromiseInterface $cmd) use ($related, $link, $pk) {
            $link->setWhere([$pk => $cmd->getPrimaryKey()]);


            $relState = $this->orm->getHeap()->get($related);

            // todo: might not be found, use existed key
            $relState->getActiveCommand()->onExecute(function (CommandPromiseInterface $outer) use ($link, $related) {

                // todo: activate command

                $link->setData([
                    $this->schema[Relation::INNER_KEY] => $this->lookupKey(
                        $this->schema[Relation::OUTER_KEY],
                        $related,
                        $outer
                    )
                ]);
            });
        });

        // TODO: comment depends on user, user does not depends on comment ?
        // todo: reset ?

        //  $orig = $state->getRelation($this->relation);

        return $link;
    }
}