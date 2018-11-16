<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;


use Spiral\ORM\Collection\PivotedCollection;
use Spiral\ORM\Collection\RelationContext;
use Spiral\ORM\Command\ChainCommand;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCommandInterface;
use Spiral\ORM\Command\Database\InsertCommand;
use Spiral\ORM\Command\DelayCommand;
use Spiral\ORM\Command\GroupCommand;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\Exception\RelationException;
use Spiral\ORM\Iterator;
use Spiral\ORM\Relation;
use Spiral\ORM\State;
use Spiral\ORM\Util\ContextStorage;

class ManyToManyRelation extends AbstractRelation
{
    public const COLLECTION = true;

    public function initArray(array $data)
    {
        $iterator = new Iterator($this->orm, $this->class, $data);

        $entities = [];
        $pivotData = new \SplObjectStorage();
        foreach ($iterator as $pivot => $entity) {
            $entities[] = $entity;

            // todo: move to the function
            if (!empty($this->define(Relation::PIVOT_ENTITY))) {
                $pivot = $this->orm->make($this->define(Relation::PIVOT_ENTITY), $pivot, State::LOADED);
            }

            $pivotData->offsetSet($entity, $pivot);
        }

        return new ContextStorage($entities, $pivotData);
    }

    public function wrapCollection($data)
    {
        if (!$data instanceof ContextStorage) {
            throw new RelationException("ManyToMany relation expects PivotData");
        }

        return new PivotedCollection($data->getData(), new RelationContext($data->getContext()));
    }

    public function queueChange(
        $parent,
        State $state,
        $related,
        $original,
        ContextCommandInterface $command
    ): CommandInterface {
        $state->setRelation($this->relation, $related);

        // schedule all

        $group = new GroupCommand();
        foreach ($related as $item) {
            // todo: we also have to udpate
            $group->addCommand($this->store($state, $item, $related->getRelationContext()->get($item)));
        }


        // insert delayed
        // update delayed

        // store or not to store?
        // cascade can only update

        return $group;
    }

    // todo: diff
    protected function store(State $parentState, $related, $context): CommandInterface
    {
        $relState = $this->orm->getHeap()->get($related);
        if (!empty($relState)) {
            // can be update

            $relState->addReference();
            if ($relState->getRefCount() > 2) {
                // todo: detect if it's the same parent over and over again?
                return new NullCommand();
            }
        }

        // todo: dirty state [?]


        $chain = new ChainCommand();
        $chain->addTargetCommand($this->orm->getMapper($related)->queueStore($related));

        $relState = $this->orm->getHeap()->get($related);

        $insert = new DelayCommand(new InsertCommand(
            $this->orm->getDatabase($this->class),
            $this->define(Relation::PIVOT_TABLE),
            []
        // todo: what if both already saved, then no need to delay
        //,
        //   [
        //        // todo: check proper key mapping
        ///    $this->define(Relation::THOUGHT_INNER_KEY) => $parentState->getKey(Relation::INNER_KEY),
        //      $this->define(Relation::THOUGHT_OUTER_KEY) => $relState->getKey(Relation::OUTER_KEY),
        //  ]
        ));

        // todo: make INSERT delayable?

        $parentState->onUpdate(function (State $state) use ($insert) {
            $insert->setContext(
                $this->define(Relation::THOUGHT_INNER_KEY),
                $state->getKey($this->define(Relation::INNER_KEY))
            );
        });

        $relState->onUpdate(function (State $state) use ($insert) {
            $insert->setContext(
                $this->define(Relation::THOUGHT_OUTER_KEY),
                $state->getKey($this->define(Relation::OUTER_KEY))
            );
        });

        $insert->setDescription('cant link');
        $chain->addCommand($insert);

        // todo: update relation state
        return $chain;
    }
}