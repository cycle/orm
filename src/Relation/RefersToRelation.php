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

        if ($state->getPrimaryKey() != null) {
            $link->setWhere([$pk => $state->getPrimaryKey()]);
        } else {
            // wait for PK
            $state->onUpdate(function (State $state) use ($link, $pk) {
                $link->setWhere([$pk => $state->getPrimaryKey()]);
            });
        }

        // or saved directly (need unification)
        $this->orm->getHeap()->onUpdate($related, function (State $state) use ($link) {
            $link->setData([
                $this->schema[Relation::INNER_KEY] => $state->getData()[$this->schema[Relation::OUTER_KEY]]
            ]);
        });

        return $link;
    }
}