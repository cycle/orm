<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualCommandInterface;
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
        ContextualCommandInterface $command
    ): CommandInterface {
        $state->setRelation($this->relation, $related);

        if (is_null($related)) {
            $command->setContext($this->define(Relation::INNER_KEY), null);
            return new NullCommand();
        }

        $relState = $this->orm->getHeap()->get($related);
        if (!empty($relState) && !empty($relState->getKey($this->define(Relation::OUTER_KEY)))) {
            $command->setContext(
                $this->define(Relation::INNER_KEY),
                $relState->getKey($this->define(Relation::OUTER_KEY))
            );

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

        if (!empty($state->getKey($pk))) {
            $link->setWhere([$pk => $state->getKey($pk)]);
        } else {
            $state->onUpdate(function (State $state) use ($link, $pk) {
                if (!empty($state->getKey($pk))) {
                    $link->setWhere([$pk => $state->getKey($pk)]);
                }
            });
        }

        // or saved directly (need unification)
        $this->orm->getHeap()->onUpdate($related, function (State $state) use ($link) {
            if (!empty($state->getKey($this->define(Relation::OUTER_KEY)))) {
                $link->setData([
                    $this->define(Relation::INNER_KEY) => $state->getKey($this->define(Relation::OUTER_KEY))
                ]);
            }
        });

        return $link;
    }
}