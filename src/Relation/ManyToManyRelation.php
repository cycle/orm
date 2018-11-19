<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;


use Spiral\ORM\Collection\PivotedCollection;
use Spiral\ORM\Collection\PivotedCollectionInterface;
use Spiral\ORM\Collection\RelationContext;
use Spiral\ORM\Command\ChainCommand;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCommandInterface;
use Spiral\ORM\Command\Database\DeleteCommand;
use Spiral\ORM\Command\Database\InsertCommand;
use Spiral\ORM\Command\DelayCommand;
use Spiral\ORM\Command\GroupCommand;
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
                $pivot = $this->orm->make(
                    $this->define(Relation::PIVOT_ENTITY),
                    $pivot,
                    State::LOADED
                );
            }

            $pivotData->offsetSet($entity, $pivot);
        }

        // todo: merge with relationContext?
        return new ContextStorage($entities, $pivotData);
    }

    public function wrapCollection($data)
    {
        if (!$data instanceof ContextStorage) {
            throw new RelationException("ManyToMany relation expects PivotData");
        }

        return new PivotedCollection(
            $data->getElements(),
            new RelationContext($data->getContext())
        );
    }

    public function queueChange(
        $parent,
        State $state,
        $related,
        $original,
        ContextCommandInterface $command,
        $id = null
    ): CommandInterface {
        /**
         * @var PivotedCollectionInterface $related
         * @var ContextStorage             $original
         */
        $state->setRelation($this->relation, new ContextStorage(
            $related->toArray(),
            $related->getRelationContext()->getContext()
        ));

        // schedule all

        /**
         * @var PivotedCollectionInterface $related
         * @var ContextStorage             $original
         */
        $relContext = $related->getRelationContext();

        $group = new GroupCommand();
        foreach ($related as $item) {
            // todo: we also have to update
            $group->addCommand($this->link($state, $item, $relContext->get($item), $id));
        }

        if (!empty($original)) {
            foreach ($original->getElements() as $item) {
                if (!$related->contains($item)) {
                    // todo: unlink!
                    $group->addCommand($this->unlink($state, $item, $id));
                }
            }
        }

        // insert delayed
        // update delayed

        // store or not to store?
        // cascade can only update

        return $group;
    }

    // todo: diff
    protected function link(State $parentState, $related, $context, $id): CommandInterface
    {

        // todo: dirty state [?]

        $chain = new ChainCommand();
        $chain->addTargetCommand($this->orm->getMapper($related)->queueStore($related, $id));

        $relState = $this->orm->getHeap()->get($related);

        // todo: check if context instance of pivot entity

        if (is_object($context)) {
            // todo: validate
            $insert = new DelayCommand(
                $this->orm->getMapper($context)->queueStore($context, $id),
                [
                    $this->define(Relation::THOUGHT_INNER_KEY),
                    $this->define(Relation::THOUGHT_OUTER_KEY)
                ]
            );
        } else {
            // todo: THIS CAN BE UPDATE COMMAND AS WELL WHEN PARENT HAS CONTEXT (!!!!!)

            // can be not empty (!!!), WAIT FOR SPECIFIC KEYS
            $insert = new DelayCommand(new InsertCommand(
                $this->orm->getDatabase($this->class),
                $this->define(Relation::PIVOT_TABLE),
                is_array($context) ? $context : []
            ),
                [
                    $this->define(Relation::THOUGHT_INNER_KEY),
                    $this->define(Relation::THOUGHT_OUTER_KEY)
                ]
            );
        }

        // TODO: CONTEXT AND DATA IS THE SAME?
        // TODO: DRY!!!

        if (!empty($parentState->getKey($this->define(Relation::INNER_KEY)))) {
            $insert->setContext(
                $this->define(Relation::THOUGHT_INNER_KEY),
                $parentState->getKey($this->define(Relation::INNER_KEY))
            );
        } else {
            $parentState->onUpdate(function (State $state) use ($insert) {
                if (!empty($state->getKey($this->define(Relation::INNER_KEY)))) {
                    $insert->setContext(
                        $this->define(Relation::THOUGHT_INNER_KEY),
                        $state->getKey($this->define(Relation::INNER_KEY))
                    );
                }
            });
        }

        // todo: DRY
        if (!empty($relState->getKey($this->define(Relation::OUTER_KEY)))) {
            $insert->setContext(
                $this->define(Relation::THOUGHT_OUTER_KEY),
                $relState->getKey($this->define(Relation::OUTER_KEY))
            );
        } else {
            $relState->onUpdate(function (State $state) use ($insert) {
                if (!empty($state->getKey($this->define(Relation::OUTER_KEY)))) {
                    $insert->setContext(
                        $this->define(Relation::THOUGHT_OUTER_KEY),
                        $state->getKey($this->define(Relation::OUTER_KEY))
                    );
                }
            });
        }

        $insert->setDescription("`{$this->class}`.`{$this->relation}` (ManyToMany)");
        $chain->addCommand($insert);

        // todo: update relation state
        return $chain;
    }

    protected function unlink(State $parentState, $related, $id): CommandInterface
    {
        // todo: DO NOT RUN IF NULL
        $delete = new DeleteCommand(
            $this->orm->getDatabase($this->class),
            $this->define(Relation::PIVOT_TABLE),
            [
                $this->define(Relation::THOUGHT_INNER_KEY) => null,
                $this->define(Relation::THOUGHT_OUTER_KEY) => null,
            ]
        );

        if (!empty($parentState->getKey($this->define(Relation::INNER_KEY)))) {
            $delete->setWhere(
                [
                    $this->define(Relation::THOUGHT_INNER_KEY) => $parentState->getKey($this->define(Relation::INNER_KEY))
                ] + $delete->getWhere()
            );
        } else {
            $parentState->onUpdate(function (State $state) use ($delete) {
                if (!empty($state->getKey($this->define(Relation::INNER_KEY)))) {
                    $delete->setWhere(
                        [
                            $this->define(Relation::THOUGHT_INNER_KEY) => $state->getKey($this->define(Relation::INNER_KEY))
                        ] + $delete->getWhere()
                    );
                }
            });
        }

        // todo: can rel state be null?
        $relState = $this->orm->getHeap()->get($related);

        // todo: DRY
        if (!empty($relState->getKey($this->define(Relation::OUTER_KEY)))) {
            $delete->setWhere(
                [
                    $this->define(Relation::THOUGHT_OUTER_KEY) => $relState->getKey($this->define(Relation::OUTER_KEY))
                ] + $delete->getWhere()
            );
        } else {
            $relState->onUpdate(function (State $state) use ($delete) {
                if (!empty($state->getKey($this->define(Relation::OUTER_KEY)))) {
                    $delete->setWhere(
                        [
                            $this->define(Relation::THOUGHT_OUTER_KEY) => $state->getKey($this->define(Relation::OUTER_KEY))
                        ] + $delete->getWhere()
                    );
                }
            });
        }

        return $delete;
    }
}