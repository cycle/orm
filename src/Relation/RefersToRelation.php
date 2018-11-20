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
use Spiral\ORM\Command\Database\LinkCommand;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\State;

class RefersToRelation extends AbstractRelation
{
    const LEADING = true;

    // todo: move to the strategy
    public function queueChange(
        $parent,
        State $state,
        $related,
        $original,
        ContextCommandInterface $command
    ): CommandInterface {
        $state->setRelation($this->relation, $related);

        // todo: if related exists we can update right in parent command context

        if (is_null($related)) {
            // todo: reset value
            return new NullCommand();
        }

        $link = new LinkCommand(
            $this->orm->getDatabase($parent),
            $this->orm->getSchema()->define(get_class($parent), Schema::TABLE)
        );
        $link->setDescription($this);

        $pk = $this->orm->getSchema()->define(get_class($parent), Schema::PRIMARY_KEY);

        // todo: NOT DRY
        // todo: PK is OUTER KEY, not really PK !!!
        if ($state->getPrimaryKey() != null) {
            $link->setWhere([$pk => $state->getPrimaryKey()]);
        } else {
            // wait for PK
            $state->onUpdate(function (State $state) use ($link, $pk) {
                if (!empty($state->getPrimaryKey())) {
                    $link->setWhere([$pk => $state->getPrimaryKey()]);
                }
            });
        }

        // or saved directly (need unification)
        // todo: can be existed
        if (!empty($relState) && !empty($relState->getKey($this->define(Relation::OUTER_KEY)))) {
        } else {
            $this->orm->getHeap()->onUpdate($related, function (State $state) use ($link) {
                if (!empty($state->getKey($this->define(Relation::OUTER_KEY)))) {
                    $link->setData([
                        $this->define(Relation::INNER_KEY) => $state->getKey($this->define(Relation::OUTER_KEY))
                    ]);
                }
            });
        }

        return $link;
    }
}