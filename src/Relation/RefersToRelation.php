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
use Spiral\ORM\Command\Database\LinkCommand;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\DependencyInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\State;

class RefersToRelation extends AbstractRelation implements DependencyInterface
{
    /**
     * @inheritdoc
     */
    public function queueDependency(
        ContextualInterface $command,
        $entity,
        State $state,
        $related,
        $original
    ): CommandInterface {
        if (is_null($related)) {
            $command->setContext($this->define(Relation::INNER_KEY), null);

            return new NullCommand();
        }

        $relState = $this->getState($related);
//        $this->promiseContext(
//            $command,
//            $relState,
//            $this->define(Relation::OUTER_KEY),
//            $state,
//            $this->define(Relation::INNER_KEY)
//        );

        if (!empty($relState) && !empty($relState->getKey($this->define(Relation::OUTER_KEY)))) {
            $command->setContext(
                $this->define(Relation::INNER_KEY),
                $relState->getKey($this->define(Relation::OUTER_KEY))
            );

            return new NullCommand();
        }

        $link = new LinkCommand(
            $this->orm->getDatabase($entity),
            $this->orm->getSchema()->define(get_class($entity), Schema::TABLE)
        );
        $link->setDescription($this);

        $pk = $this->orm->getSchema()->define(get_class($entity), Schema::PRIMARY_KEY);

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

    /**
     * @inheritdoc
     */
    public function queueRelation($entity, State $state, $related, $original): CommandInterface
    {
        return new NullCommand();
    }
}