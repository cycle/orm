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
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Control\ContextualSequence;
use Spiral\ORM\Command\Control\Defer;
use Spiral\ORM\Command\Control\Sequence;
use Spiral\ORM\Command\Database\DeleteCommand;
use Spiral\ORM\Command\Database\Insert;
use Spiral\ORM\Exception\RelationException;
use Spiral\ORM\Iterator;
use Spiral\ORM\Relation;
use Spiral\ORM\State;
use Spiral\ORM\Util\ContextStorage;

class ManyToManyRelation extends AbstractRelation
{
    use Relation\Traits\PromiseTrait;

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

    public function extract($relData)
    {
        return new ContextStorage(
            $relData->toArray(),
            $relData->getRelationContext()->getContext()
        );
    }

    /**
     * @inheritdoc
     */
    public function queueRelation($entity, State $state, $related, $original): CommandInterface
    {
        /**
         * @var ContextStorage $related
         * @var ContextStorage $original
         */
        $original = $original ?? new ContextStorage([], new \SplObjectStorage());

        $sequence = new Sequence();
        foreach ($related->getElements() as $item) {
            // todo: what about original, check the change?
            $sequence->addCommand($this->link(
                $state,
                $item,
                $related->get($item),
                $original->get($item)
            ));
        }

        foreach ($original->getElements() as $item) {
            if (!$related->contains($item)) {
                $sequence->addCommand($this->unlink($state, $item));
            }
        }

        return $sequence;
    }

    // todo: diff
    protected function link(State $parentState, $related, $context): CommandInterface
    {
        $relStore = $this->orm->getMapper($related)->queueStore($related);
        $relState = $this->getState($related);

        $chain = new ContextualSequence();
        $chain->addPrimary($relStore);

        if (is_object($context)) {
            // todo: check if context instance of pivot entity
            $cmd = $this->orm->getMapper($context)->queueStore($context);
            $ctxState = $this->getState($context);
        } else {
            // todo: update existed?
            // todo: store database name (!!) in relation
            $cmd = new Insert(
                $this->orm->getDatabase($this->class),
                $this->define(Relation::PIVOT_TABLE),
                $context ?? []
            );
        }

        $insert = new Defer(
            $cmd,
            [
                $this->define(Relation::THOUGHT_INNER_KEY),
                $this->define(Relation::THOUGHT_OUTER_KEY)
            ],
            (string)$this
        );

        // TODO: DRY!!!
        // todo: ENTITY IS DIFFERENT!!!

        // todo: DO NOT UPDATE CONTEXT

        $this->promiseContext(
            $insert,
            $parentState,
            $this->define(Relation::INNER_KEY),
            null,
            $this->define(Relation::THOUGHT_INNER_KEY)
        );

        $this->promiseContext(
            $insert,
            $relState,
            $this->define(Relation::OUTER_KEY),
            null,
            $this->define(Relation::THOUGHT_OUTER_KEY)
        );

        $chain->addCommand($insert);

        // todo: update relation state
        return $chain;
    }

    protected function unlink(State $parentState, $related): CommandInterface
    {
        // todo: we should only remove it if related entity is not null!!

        // todo: DO NOT RUN IF NULL
        $delete = new DeleteCommand(
            $this->orm->getDatabase($this->class),
            $this->define(Relation::PIVOT_TABLE),
            [
                $this->define(Relation::THOUGHT_INNER_KEY) => null,
                $this->define(Relation::THOUGHT_OUTER_KEY) => null,
            ]
        );

        // TODO: DRY
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