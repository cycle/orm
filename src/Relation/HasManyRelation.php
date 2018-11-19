<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Doctrine\Common\Collections\Collection;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ConditionalCommand;
use Spiral\ORM\Command\ContextCommandInterface;
use Spiral\ORM\Command\GroupCommand;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

class HasManyRelation extends AbstractRelation
{
    public const COLLECTION = true;

    public function queueChange(
        $parent,
        State $state,
        $related,
        $original,
        ContextCommandInterface $command,
        $id = null
    ): CommandInterface {
        if ($related instanceof Collection) {
            $related = $related->toArray();
        }

        // removed
        $removed = array_udiff($original ?? [], $related, function ($a, $b) {
            return strcmp(spl_object_hash($a), spl_object_hash($b));
        });

        $state->setRelation($this->relation, $related);

        $group = new GroupCommand();
        foreach ($related as $item) {
            $group->addCommand($this->store($state, $item, $id));
        }

        foreach ($removed as $item) {
            $group->addCommand($this->remove($state, $item, $id));
        }

        return $group;
    }

    // todo: diff
    protected function store(State $parentState, $related, $id): CommandInterface
    {
        $relState = $this->orm->getHeap()->get($related);
        if (!empty($relState)) {
            $relState->addReference();
        }

        // todo: dirty state [?]
        $inner = $this->orm->getMapper(get_class($related))->queueStore($related, $id);

        if (!empty($parentState->getKey($this->define(Relation::INNER_KEY)))) {
            $inner->setContext(
                $this->define(Relation::OUTER_KEY),
                $parentState->getKey($this->define(Relation::INNER_KEY))
            );
        } else {
            // what if multiple keys set
            $parentState->onUpdate(function (State $state) use ($inner) {

                $inner->setContext(
                    $this->define(Relation::OUTER_KEY),
                    $state->getKey($this->define(Relation::INNER_KEY))
                );

                // todo: morph key
            });
        }

        // todo: update relation state

        return $inner;
    }

    protected function remove(State $parentState, $related): CommandInterface
    {
        $origState = $this->orm->getHeap()->get($related);
        $origState->delRef();

        return new ConditionalCommand(
            $this->orm->getMapper($related)->queueDelete($related),
            function () use ($origState) {
                return $origState->getRefCount() == 0;
            }
        );
    }
}