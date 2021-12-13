<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry\Implementation;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Registry\IndexProviderInterface;
use Cycle\ORM\Registry\MapperProviderInterface;
use Cycle\ORM\Registry\RelationProviderInterface;
use Cycle\ORM\Registry\RepositoryProviderInterface;
use Cycle\ORM\Registry\SourceProviderInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use WeakReference;

/**
 * @internal
 */
final class EntityRegistry implements
    MapperProviderInterface,
    RepositoryProviderInterface,
    RelationProviderInterface,
    IndexProviderInterface
{
    /** @var MapperInterface[] */
    private array $mappers = [];

    /** @var RepositoryInterface[] */
    private array $repositories = [];

    /** @var RelationMap[] */
    private array $relMaps = [];

    private array $indexes = [];

    /**
     * @var WeakReference<ORMInterface>
     */
    private WeakReference $orm;

    public function __construct(
        ORMInterface $orm,
        private SourceProviderInterface $sourceProvider,
        private SchemaInterface $schema,
        private FactoryInterface $factory
    ) {
        $this->orm = WeakReference::create($orm);
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->mappers = [];
        $this->relMaps = [];
        $this->indexes = [];
        $this->sources = [];
        $this->repositories = [];
    }

    public function getMapper(string $entity): MapperInterface
    {
        return $this->mappers[$entity] ?? ($this->mappers[$entity] = $this->factory->mapper($this->orm->get(), $entity));
    }

    public function getRepository(string $entity): RepositoryInterface
    {
        if (isset($this->repositories[$entity])) {
            return $this->repositories[$entity];
        }

        $select = null;

        if ($this->schema->define($entity, SchemaInterface::TABLE) !== null) {
            $select = new Select($this->orm->get(), $entity);
            $select->scope($this->sourceProvider->getSource($entity)->getScope());
        }

        return $this->repositories[$entity] = $this->factory->repository($this->orm->get(), $this->schema, $entity, $select);
    }

    public function getIndexes(string $role): array
    {
        if (isset($this->indexes[$role])) {
            return $this->indexes[$role];
        }

        $pk = $this->schema->define($role, SchemaInterface::PRIMARY_KEY);
        $keys = $this->schema->define($role, SchemaInterface::FIND_BY_KEYS) ?? [];

        return $this->indexes[$role] = \array_unique(\array_merge([$pk], $keys), SORT_REGULAR);
    }

    /**
     * Get relation map associated with the given class.
     */
    public function getRelationMap(string $role): RelationMap
    {
        return $this->relMaps[$role] ?? ($this->relMaps[$role] = RelationMap::build($this->orm->get(), $role));
    }
}
