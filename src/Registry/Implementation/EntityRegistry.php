<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry\Implementation;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Registry\RelationProviderInterface;
use Cycle\ORM\Registry\RepositoryProviderInterface;
use Cycle\ORM\Registry\SourceProviderInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;

/**
 * @internal
 */
final class EntityRegistry implements
    RepositoryProviderInterface,
    RelationProviderInterface
{
    /** @var array<non-empty-string, RepositoryInterface> */
    private array $repositories = [];

    /** @var array<non-empty-string, RelationMap> */
    private array $relMaps = [];

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
        $this->relMaps = [];
        $this->repositories = [];
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

    /**
     * Get relation map associated with the given class.
     */
    public function getRelationMap(string $entity): RelationMap
    {
        return $this->relMaps[$entity] ?? ($this->relMaps[$entity] = RelationMap::build($this->orm, $entity));
    }
}
