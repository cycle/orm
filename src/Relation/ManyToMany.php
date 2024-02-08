<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Collection\Pivoted\PivotedCollectionInterface;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Iterator;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\Reference\EmptyReference;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Loader\ManyToManyLoader;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\RootLoader;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use SplObjectStorage;
use Traversable;

/**
 * @internal
 */
class ManyToMany extends Relation\AbstractRelation
{
    /** @var string[] */
    protected array $throughInnerKeys;

    /** @var string[] */
    protected array $throughOuterKeys;

    protected string $pivotRole;

    protected EntityFactoryInterface $entityFactory;
    protected SourceProviderInterface $sourceProvider;
    protected FactoryInterface $factory;
    private HeapInterface $heap;

    public function __construct(
        ORMInterface $orm,
        private string $role,
        string $name,
        string $target,
        array $schema
    ) {
        parent::__construct($orm, $role, $name, $target, $schema);
        $this->heap = $orm->getHeap();
        $this->sourceProvider = $orm->getService(SourceProviderInterface::class);
        $this->entityFactory = $orm->getService(EntityFactoryInterface::class);
        $this->factory = $orm->getFactory();
        $this->pivotRole = $this->schema[Relation::THROUGH_ENTITY];

        $this->throughInnerKeys = (array)$this->schema[Relation::THROUGH_INNER_KEY];
        $this->throughOuterKeys = (array)$this->schema[Relation::THROUGH_OUTER_KEY];
    }

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void
    {
        $node = $tuple->node;

        /** @var PivotedStorage|ReferenceInterface|null $original */
        $original = $node->getRelation($this->getName());
        $tuple->state->setRelation($this->getName(), $related);

        if ($original instanceof ReferenceInterface) {
            if (!$load && $related === $original && !$original->hasValue()) {
                $this->finalize($pool, $tuple, $related);
                return;
            }
            $this->resolve($original, true);
            $original = $original->getValue();
            $node->setRelation($this->getName(), $original);
        }
        $original = $this->extract($original);

        if ($related instanceof ReferenceInterface && $this->resolve($related, true) !== null) {
            $related = $related->getValue();
            $tuple->state->setRelation($this->getName(), $related);
        }
        $related = $this->extractRelated($related, $original);
        // $tuple->state->setStorage($this->pivotEntity, $related);
        $tuple->state->setRelation($this->getName(), $related);

        // un-link old elements
        foreach ($original as $item) {
            if (!$related->has($item)) {
                $pivot = $original->get($item);
                $this->deleteChild($pool, $pivot, $item);
                $original->getContext()->offsetUnset($item);
            }
        }

        if ($this->inversion === null && \count($related) === 0) {
            $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            return;
        }
        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            $this->newLink($pool, $tuple, $related, $item);
        }
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        $related = $tuple->state->getRelation($this->getName());

        if ($related instanceof ReferenceInterface && !$related->hasValue()) {
            $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            return;
        }

        $this->finalize($pool, $tuple, $related);
    }

    public function init(EntityFactoryInterface $factory, Node $node, array $data): iterable
    {
        $elements = [];
        $pivotData = new SplObjectStorage();

        $iterator = Iterator::createWithServices(
            $this->heap,
            $this->ormSchema,
            $this->entityFactory,
            $this->target,
            $data,
            true
        );
        foreach ($iterator as $pivot => $entity) {
            if (!\is_array($pivot)) {
                // skip partially selected entities (DB level filter)
                continue;
            }

            $pivotData[$entity] = $factory->make($this->pivotRole, $pivot, Node::MANAGED);
            $elements[] = $entity;
        }
        $collection = new PivotedStorage($elements, $pivotData);
        $node->setRelation($this->name, $collection);

        return $this->collect($collection);
    }

    public function cast(?array $data): array
    {
        if (!$data) {
            return [];
        }
        /**
         * @var array<non-empty-string, MapperInterface> $targetMappers Target Mappers cache
         * @var array<non-empty-string, MapperInterface> $pivotMappers Pivot Mappers cache
         */
        $pivotMappers = [];
        $targetMappers = [];

        foreach ($data as $key => $pivot) {
            if (isset($pivot['@'])) {
                $d = $pivot['@'];
                // break link
                unset($pivot['@']);

                $targetRole = $d[LoaderInterface::ROLE_KEY] ?? $this->target;
                $pivot['@'] = ($targetMappers[$targetRole] ??= $this->mapperProvider->getMapper($targetRole))->cast($d);
            }
            // break link
            unset($data[$key]);

            $pivotRole = $pivot[LoaderInterface::ROLE_KEY] ?? $this->pivotRole;
            $data[$key] = ($pivotMappers[$pivotRole] ??= $this->mapperProvider->getMapper($pivotRole))->cast($pivot);
        }

        return $data;
    }

    public function collect(mixed $data): iterable
    {
        return $this->factory->collection(
            $this->schema[Relation::COLLECTION_TYPE] ?? null
        )->collect($data);
    }

    public function extract(?iterable $data): PivotedStorage
    {
        return match (true) {
            $data instanceof PivotedStorage => $data,
            $data instanceof PivotedCollectionInterface => new PivotedStorage(
                $data->toArray(),
                $data->getPivotContext()
            ),
            $data instanceof \Doctrine\Common\Collections\Collection => new PivotedStorage($data->toArray()),
            $data === null => new PivotedStorage(),
            $data instanceof Traversable => new PivotedStorage(iterator_to_array($data)),
            default => new PivotedStorage((array)$data),
        };
    }

    public function extractRelated(?iterable $data, PivotedStorage $original): PivotedStorage
    {
        $related = $this->extract($data);
        if (\count($original) === 0) {
            return $related;
        }
        // Merge pivots
        foreach ($related as $item) {
            if ($related->hasContext($item)) {
                continue;
            }
            if ($original->hasContext($item)) {
                $related->set($item, $original->getContext()->offsetGet($item));
            }
        }
        return $related;
    }

    public function initReference(Node $node): ReferenceInterface
    {
        $scope = [];
        $nodeData = $node->getData();
        foreach ($this->innerKeys as $key) {
            if (!isset($nodeData[$key])) {
                return new EmptyReference($node->getRole(), new PivotedStorage());
            }
            $scope[$key] = $nodeData[$key];
        }

        return new Reference($this->target, $scope);
    }

    public function resolve(ReferenceInterface $reference, bool $load): ?iterable
    {
        if ($reference->hasValue()) {
            return $reference->getValue();
        }
        if ($load === false) {
            return null;
        }
        $scope = $reference->getScope();
        if ($scope === []) {
            $result = new PivotedStorage();
            $reference->setValue($result);
            return $result;
        }

        $source = $this->sourceProvider->getSource($this->target);
        // getting scoped query
        $query = (new RootLoader(
            $this->ormSchema,
            $this->sourceProvider,
            $this->factory,
            $this->target,
            loadRelations: false,
        ))->buildQuery();

        // responsible for all the scoping
        $loader = new ManyToManyLoader(
            $this->ormSchema,
            $this->sourceProvider,
            $this->factory,
            $source->getTable(),
            $this->target,
            $this->schema
        );

        /** @var ManyToManyLoader $loader */
        $loader = $loader->withContext($loader, [
            'scope' => $source->getScope(),
            'as' => $this->target,
            'method' => JoinableLoader::POSTLOAD,
        ]);

        $query = $loader->configureQuery($query, [$scope]);

        // we are going to add pivot node into virtual root node (only ID) to aggregate the results
        $root = new RootNode(
            (array)$this->schema[Relation::INNER_KEY],
            (array)$this->schema[Relation::INNER_KEY]
        );

        $node = $loader->createNode();
        $root->linkNode('output', $node);

        // emulate presence of parent entity
        $root->parseRow(0, $this->mapperProvider->getMapper($this->role)->uncast($scope));

        $iterator = $query->getIterator();
        foreach ($iterator as $row) {
            $node->parseRow(0, $row);
        }
        $iterator->close();

        // load all eager relations, forbid loader to re-fetch data (make it think it was joined)
        $loader->withContext($loader, ['method' => JoinableLoader::INLOAD])->loadData($node);

        $elements = [];
        $pivotData = new SplObjectStorage();
        $iterator = Iterator::createWithServices(
            $this->heap,
            $this->ormSchema,
            $this->entityFactory,
            $this->target,
            $root->getResult()[0]['output'],
            true,
            typecast: true
        );
        foreach ($iterator as $pivot => $entity) {
            $pivotData[$entity] = $this->entityFactory->make(
                $this->schema[Relation::THROUGH_ENTITY],
                $pivot,
                Node::MANAGED,
                typecast: true
            );

            $elements[] = $entity;
        }
        $result = new PivotedStorage($elements, $pivotData);
        $reference->setValue($result);
        return $result;
    }

    protected function applyPivotChanges(State $parentState, State $state): void
    {
        foreach ($this->innerKeys as $i => $innerKey) {
            $state->register($this->throughInnerKeys[$i], $parentState->getValue($innerKey));
        }
    }

    private function deleteChild(Pool $pool, ?object $pivot, object $child, ?Node $relatedNode = null): void
    {
        // todo: add supporting for nullable pivot entities?
        if ($pivot !== null) {
            $pool->attachDelete($pivot, $this->isCascade());
        }
        $pool->attachStore($child, true);
    }

    protected function newLink(Pool $pool, Tuple $tuple, PivotedStorage $storage, object $related): void
    {
        $rTuple = $pool->attachStore($related, $this->isCascade());
        $this->assertValid($rTuple->node);

        $pivot = $storage->get($related);
        if (!\is_object($pivot)) {
            // first time initialization
            $pivot = $this->initPivot($tuple->entity, $storage, $rTuple, $pivot);
            $storage->set($related, $pivot);
        }

        $pTuple = $pool->attachStore($pivot, $this->isCascade());
        // $pRelationName = $tuple->node->getRole() . '.' . $this->getName() . ':' . $this->pivotEntity;
        // $pNode->setRelationStatus($pRelationName, RelationInterface::STATUS_RESOLVED);

        foreach ($this->throughInnerKeys as $i => $pInnerKey) {
            $pTuple->state->register($pInnerKey, $tuple->state->getTransactionData()[$this->innerKeys[$i]] ?? null);
        }

        if ($this->inversion === null) {
            // send the Pivot into child's State for the ShadowHasMany relation
            // $relName = $tuple->node->getRole() . '.' . $this->name . ':' . $this->target;
            $relName = $this->getTargetRelationName();
            $pivots = $rTuple->state->getRelations()[$relName] ?? [];
            $pivots[] = $pivot;
            $rTuple->state->setRelation($relName, $pivots);
        } else {
            $rTuple->state->addToStorage($this->inversion, $pTuple->state);
        }
    }

    /**
     * Since many to many relation can overlap from two directions we have to properly resolve the pivot entity upon
     * it's generation. This is achieved using temporary mapping associated with each of the entity states.
     */
    protected function initPivot(object $parent, PivotedStorage $storage, Tuple $rTuple, ?array $pivot): ?object
    {
        if ($this->inversion !== null) {
            $relatedStorage = $rTuple->state->getRelation($this->inversion);
            if ($relatedStorage instanceof PivotedStorage && $relatedStorage->hasContext($parent)) {
                return $relatedStorage->get($parent);
            }
        }

        $entity = $this->entityFactory->make($this->pivotRole, $pivot ?? []);
        $storage->set($rTuple->entity, $entity);
        return $entity;
    }

    private function finalize(Pool $pool, Tuple $tuple, mixed $related): void
    {
        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);

        $relationName = $tuple->node->getRole() . '.' . $this->name . ':' . $this->pivotRole;
        $pStates = [];
        foreach ($related as $item) {
            $pivot = $related->get($item);
            if ($pivot !== null) {
                $pTuple = $pool->offsetGet($pivot);
                $this->applyPivotChanges($tuple->state, $pTuple->state);
                $pStates[] = $pTuple->state;
                $pTuple->state->setRelationStatus($relationName, RelationInterface::STATUS_RESOLVED);
            }
        }
        if ($this->inversion !== null) {
            $storage = $tuple->state->getStorage($this->name);
            foreach ($storage as $pState) {
                if (\in_array($pState, $pStates, true)) {
                    continue;
                }
                $this->applyPivotChanges($tuple->state, $pState);
                $pState->setRelationStatus($relationName, RelationInterface::STATUS_RESOLVED);
            }
            $tuple->state->clearStorage($this->name);
        }
    }
}
