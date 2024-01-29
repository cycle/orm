<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Exception\Relation\BadRelationValueException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\EmptyReference;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\Traits\HasSomeTrait;
use Cycle\ORM\Select;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Provides the ability to own the collection of entities.
 *
 * @internal
 */
class HasMany extends AbstractRelation
{
    use HasSomeTrait;

    protected FactoryInterface $factory;
    protected Select $select;

    public function __construct(ORMInterface $orm, string $role, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $role, $name, $target, $schema);
        $sourceProvider = $orm->getService(SourceProviderInterface::class);
        $this->factory = $orm->getFactory();

        // Prepare Select Statement
        $this->select = (new Select($orm, $this->target))
            ->scope($sourceProvider->getSource($this->target)->getScope())
            ->orderBy($this->schema[Relation::ORDER_BY] ?? []);
    }

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());
        $tuple->state->setRelation($this->getName(), $related);

        if ($original instanceof ReferenceInterface) {
            if (!$load && $this->compareReferences($original, $related) && !$original->hasValue()) {
                $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
                return;
            }
            $original = $this->resolve($original, true);
            $node->setRelation($this->getName(), $original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related, true);
            $tuple->state->setRelation($this->getName(), $related);
        } elseif (!\is_iterable($related)) {
            if ($related === null) {
                $related = $this->collect([]);
            } else {
                throw new BadRelationValueException(\sprintf(
                    'Value for Has Many relation must be of the iterable type, %s given.',
                    \get_debug_type($related),
                ));
            }
        }
        foreach ($this->calcDeleted($related, $original ?? []) as $item) {
            $this->deleteChild($pool, $tuple, $item);
        }

        if (\count($related) === 0) {
            $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            return;
        }
        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);

        // $relationName = $this->getTargetRelationName()
        // Store new and existing items
        foreach ($related as $item) {
            $rTuple = $pool->attachStore($item, true);
            $this->assertValid($rTuple->node);
            if ($this->isNullable()) {
                // todo?
                // $rNode->setRelationStatus($relationName, RelationInterface::STATUS_DEFERRED);
            }
        }
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        $node = $tuple->node;
        $related = $tuple->state->getRelation($this->getName());
        $related = $this->extract($related);

        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);

        if ($related instanceof ReferenceInterface && !$related->hasValue()) {
            return;
        }

        // Fill related
        $relationName = $this->getTargetRelationName();
        foreach ($related as $item) {
            /** @var Tuple $rTuple */
            $rTuple = $pool->offsetGet($item);

            if ($this->inversion !== null) {
                if ($rTuple->node->getStatus() === Node::NEW) {
                    // For existing entities it can be unwanted
                    // if Reference to Parent will be rewritten by Parent Entity
                    $rTuple->state->setRelation($relationName, $tuple->entity);
                }

                if ($rTuple->state->getRelationStatus($relationName) === RelationInterface::STATUS_PREPARE) {
                    continue;
                }
            }
            $this->applyChanges($tuple, $rTuple);
            $rTuple->state->setRelationStatus($relationName, RelationInterface::STATUS_RESOLVED);
        }
    }

    /**
     * Init relation state and entity collection.
     */
    public function init(EntityFactoryInterface $factory, Node $node, array $data): iterable
    {
        $elements = [];
        foreach ($data as $item) {
            $elements[] = $factory->make($this->target, $item, Node::MANAGED);
        }

        $node->setRelation($this->getName(), $elements);
        return $this->collect($elements);
    }

    public function cast(?array $data): array
    {
        if (!$data) {
            return [];
        }

        /** @var array<non-empty-string, MapperInterface> $mappers Mappers cache */
        $mappers = [];

        foreach ($data as $key => $item) {
            $role = $item[LoaderInterface::ROLE_KEY] ?? $this->target;
            $mappers[$role] ??= $this->mapperProvider->getMapper($role);
            // break link
            unset($data[$key]);
            $data[$key] = $mappers[$role]->cast($item);
        }
        return $data;
    }

    public function initReference(Node $node): ReferenceInterface
    {
        $scope = $this->getReferenceScope($node);
        return $scope === null
            ? new EmptyReference($node->getRole(), [])
            : new Reference($this->target, $scope);
    }

    protected function getReferenceScope(Node $node): ?array
    {
        $scope = [];
        $nodeData = $node->getData();
        foreach ($this->innerKeys as $i => $key) {
            if (!isset($nodeData[$key])) {
                return null;
            }
            $scope[$this->outerKeys[$i]] = $nodeData[$key];
        }
        return $scope;
    }

    public function resolve(ReferenceInterface $reference, bool $load): ?iterable
    {
        if ($reference->hasValue()) {
            return $reference->getValue();
        }
        if ($reference->getScope() === []) {
            // nothing to proxy to
            $reference->setValue([]);
            return [];
        }
        if ($load === false) {
            return null;
        }

        $scope = array_merge($reference->getScope(), $this->schema[Relation::WHERE] ?? []);

        $iterator = (clone $this->select)->where($scope)->getIterator(findInHeap: true);
        $result = \iterator_to_array($iterator, false);

        $reference->setValue($result);

        return $result;
    }

    public function collect(mixed $data): iterable
    {
        if (!\is_iterable($data)) {
            throw new \InvalidArgumentException('Collected data in the HasMany relation should be iterable.');
        }
        return $this->factory->collection(
            $this->schema[Relation::COLLECTION_TYPE] ?? null
        )->collect($data);
    }

    /**
     * Convert entity data into array.
     */
    public function extract(mixed $data): array
    {
        if ($data instanceof \Doctrine\Common\Collections\Collection) {
            return $data->toArray();
        }
        if ($data instanceof \Traversable) {
            return \iterator_to_array($data);
        }
        return \is_array($data) ? $data : [];
    }

    /**
     * Return objects which are subject of removal.
     */
    protected function calcDeleted(iterable $related, iterable $original): array
    {
        $related = $this->extract($related);
        $original = $this->extract($original);
        return array_udiff(
            $original ?? [],
            $related,
            // static fn(object $a, object $b): int => strcmp(spl_object_hash($a), spl_object_hash($b))
            static fn (object $a, object $b): int => (int)($a === $b) - 1
        );
    }
}
