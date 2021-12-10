<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Registry\SourceProviderInterface;
use Cycle\ORM\Select\SourceInterface;
use WeakReference;

final class EntityRegistry implements EntityRegistryInterface
{
    /** @var MapperInterface[] */
    private array $mappers = [];

    /** @var TypecastInterface[] */
    private array $typecasts = [];

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

    public function getMapper(string $role): MapperInterface
    {
        return $this->mappers[$role] ?? ($this->mappers[$role] = $this->factory->mapper($this->orm->get(), $role));
    }

    public function getRepository(string $role): RepositoryInterface
    {
        if (isset($this->repositories[$role])) {
            return $this->repositories[$role];
        }

        $select = null;

        if ($this->schema->define($role, SchemaInterface::TABLE) !== null) {
            $select = new Select($this->orm->get(), $role);
            $select->scope($this->sourceProvider->getSource($role)->getScope());
        }

        return $this->repositories[$role] = $this->factory->repository($this->orm->get(), $this->schema, $role, $select);
    }

    public function getTypecast(string $role): ?TypecastInterface
    {
        return \array_key_exists($role, $this->typecasts)
            ? $this->typecasts[$role]
            : ($this->typecasts[$role] = $this->factory->typecast($this->orm->get(), $role));
    }

    public function getIndexes(string $role): array
    {
        if (isset($this->indexes[$role])) {
            return $this->indexes[$role];
        }

        $pk = $this->schema->define($role, SchemaInterface::PRIMARY_KEY);
        $keys = $this->schema->define($role, SchemaInterface::FIND_BY_KEYS) ?? [];

        return $this->indexes[$role] = array_unique(array_merge([$pk], $keys), SORT_REGULAR);
    }

    /**
     * Get relation map associated with the given class.
     */
    public function getRelationMap(string $role): RelationMap
    {
        return $this->relMaps[$role] ?? ($this->relMaps[$role] = RelationMap::build($this->orm->get(), $role));
    }
}
