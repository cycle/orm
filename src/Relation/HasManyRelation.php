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
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

class HasManyRelation extends AbstractRelation
{
    public const COLLECTION = true;

    public function queueChange(
        $parent,
        State $state,
        $related,
        ContextCommandInterface $command
    ): CommandInterface {
        $orig = $state->getRelation($this->relation);

        if ($related instanceof Collection) {
            $related = $related->toArray();
        }

        // removed
        $removed = array_udiff($orig ?? [], $related, function ($a, $b) {
            return strcmp(spl_object_hash($a), spl_object_hash($b));
        });

        $state->setRelation($this->relation, $related);

        $group = new GroupCommand();
        foreach ($related as $item) {
            $group->addCommand($this->add($command, $parent, $state, $item));
        }

        foreach ($removed as $item) {
            $group->addCommand($this->remove($command, $parent, $state, $item));
        }

        return $group;
    }

    // todo: diff
    protected function add(ContextCommandInterface $command, $parent, State $state, $related): CommandInterface
    {
        $relState = $this->orm->getHeap()->get($related);
        if (!empty($relState)) {
            $relState->addReference();
            if ($relState->getRefCount() > 2) {
                // todo: detect if it's the same parent over and over again?
                return new NullCommand();
            }
        }

        // todo: dirty state [?]
        $inner = $this->orm->getMapper(get_class($related))->queueStore($related);

        if (!empty($state->getKey($this->define(Relation::INNER_KEY)))) {
            $inner->setContext(
                $this->define(Relation::OUTER_KEY),
                $state->getKey($this->define(Relation::INNER_KEY))
            );
        } else {
            // what if multiple keys set
            $state->onUpdate(function (State $state) use ($inner) {

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

    protected function remove(ContextCommandInterface $command, $parent, State $state, $related): CommandInterface
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