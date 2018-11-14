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
use Spiral\ORM\Command\CommandPromiseInterface;
use Spiral\ORM\Command\ConditionalCommand;
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
        CommandPromiseInterface $command
    ): CommandInterface {
        $related = $this->getRelated($parent);
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
            $group->addCommand($this->add($command, $parent, $item));
        }

        foreach ($removed as $item) {
            $group->addCommand($this->remove($command, $parent, $item));
        }

        return $group;
    }


    // todo: diff
    protected function add(CommandPromiseInterface $command, $parent, $related): CommandInterface
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

        // syncing (TODO: CHECK IF NOT SYNCED ALREADY)
        $command->onExecute(function (CommandPromiseInterface $command) use ($inner, $parent) {
            $inner->setContext(
                $this->schema[Relation::OUTER_KEY],
                $this->lookupKey($this->schema[Relation::INNER_KEY], $parent, $command)
            );

            // todo: MORPH KEY
        });

        // todo: update relation state

        return $inner;
    }

    protected function remove(CommandPromiseInterface $command, $parent, $related): CommandInterface
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