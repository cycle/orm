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
    /** @var array<non-empty-string, MapperInterface> */
    private array $mappers = [];

    /** @var array<non-empty-string, RepositoryInterface> */
    private array $repositories = [];

    /** @var array<non-empty-string, RelationMap> */
    private array $relMaps = [];

    private array $indexes = [];

    public function __construct(
        private ORMInterface $orm,
        private SourceProviderInterface $sourceProvider,
        private SchemaInterface $schema,
        private FactoryInterface $factory
    ) {
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->mappers = [];
        $this->relMaps = [];
        $this->indexes = [];
        $this->repositories = [];
    }

    public function getMapper(string $entity): MapperInterface
    {
        return $this->mappers[$entity] ?? ($this->mappers[$entity] = $this->factory->mapper($this->orm, $entity));
    }

    public function getRepository(string $entity): RepositoryInterface
    {
        if (isset($this->repositories[$entity])) {
            return $this->repositories[$entity];
        }

        $select = null;

        if ($this->schema->define($entity, SchemaInterface::TABLE) !== null) {
            $select = new Select($this->orm, $entity);
            $select->scope($this->sourceProvider->getSource($entity)->getScope());
        }

        return $this->repositories[$entity] = $this->factory->repository($this->orm, $this->schema, $entity, $select);
    }

    public function getIndexes(string $entity): array
    {
        if (isset($this->indexes[$entity])) {
            return $this->indexes[$entity];
        }

        $pk = $this->schema->define($entity, SchemaInterface::PRIMARY_KEY);
        $keys = $this->schema->define($entity, SchemaInterface::FIND_BY_KEYS) ?? [];

        return $this->indexes[$entity] = \array_unique(\array_merge([$pk], $keys), SORT_REGULAR);
    }

    /**
     * Get relation map associated with the given class.
     */
    public function getRelationMap(string $entity): RelationMap
    {
        return $this->relMaps[$entity] ?? ($this->relMaps[$entity] = RelationMap::build($this->orm, $entity));
    }
}
