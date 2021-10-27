<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Exception\FactoryException;
use Cycle\ORM\Exception\TypecastException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\ScopeInterface;
use Cycle\ORM\Select\Loader\ParentLoader;
use Cycle\ORM\Select\Loader\SubclassLoader;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Select\SourceInterface;
use Spiral\Core\Container;
use Spiral\Core\FactoryInterface as CoreFactory;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;

final class Factory implements FactoryInterface
{
    private RelationConfig $config;
    private CoreFactory $factory;

    /** @var array<string, string> */
    private array $defaults = [
        Schema::REPOSITORY => Repository::class,
        Schema::SOURCE => Source::class,
        Schema::MAPPER => Mapper::class,
        Schema::SCOPE => null,
    ];

    /** @var array<string, CollectionFactoryInterface> */
    private array $collectionFactoryAlias = [];

    /**
     * @var array<string, CollectionFactoryInterface>
     * @psalm-var array<class-string, CollectionFactoryInterface>
     */
    private array $collectionFactoryInterface = [];

    private CollectionFactoryInterface $defaultCollectionFactory;

    public function __construct(
        private DatabaseProviderInterface $dbal,
        RelationConfig $config = null,
        CoreFactory $factory = null,
        CollectionFactoryInterface $defaultCollectionFactory = null
    ) {
        $this->config = $config ?? RelationConfig::getDefault();
        $this->factory = $factory ?? new Container();
        $this->defaultCollectionFactory = $defaultCollectionFactory ?? new ArrayCollectionFactory();
    }

    public function make(
        string $alias,
        array $parameters = []
    ): mixed {
        return $this->factory->make($alias, $parameters);
    }

    public function typecast(ORMInterface $orm, string $role): ?TypecastInterface
    {
        $ts = $orm->getSchema()->define($role, SchemaInterface::TYPECAST);
        $database = $orm->getEntityRegistry()->getSource($role)->getDatabase();

        if ($ts === null) {
            return null;
        }

        // Create basic typecast implementation
        if (\is_array($ts)) {
            return new Typecast($ts, $database);
        }

        if (\is_string($ts)) {
            $ts = $this->factory->make(
                $ts,
                [
                    'database' => $database,
                    'orm' => $orm,
                    'role' => $role,
                ]
            );
        }

        if (!$ts instanceof TypecastInterface) {
            throw new FactoryException(\sprintf('Bad typecast declaration for the `%s` role.', $role));
        }

        return $ts;
    }

    public function mapper(ORMInterface $orm, string $role): MapperInterface
    {
        $schema = $orm->getSchema();
        $class = $schema->define($role, Schema::MAPPER) ?? $this->defaults[Schema::MAPPER];

        if (!\is_subclass_of($class, MapperInterface::class)) {
            throw new TypecastException(sprintf('%s does not implement %s.', $class, MapperInterface::class));
        }

        return $this->factory->make(
            $class,
            [
                'orm' => $orm,
                'role' => $role,
                'schema' => $schema->define($role, Schema::SCHEMA),
            ]
        );
    }

    public function loader(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        string $relation
    ): LoaderInterface {
        if ($relation === self::PARENT_LOADER) {
            $parent = $schema->define($role, SchemaInterface::PARENT);
            return new ParentLoader($orm, $role, $parent);
        }
        if ($relation === self::CHILD_LOADER) {
            $parent = $schema->define($role, SchemaInterface::PARENT);
            return new SubclassLoader($orm, $parent, $role);
        }
        $definition = $schema->defineRelation($role, $relation);

        return $this->config->getLoader($definition[Relation::TYPE])->resolve(
            $this->factory,
            [
                'orm' => $orm,
                'role' => $role,
                'name' => $relation,
                'target' => $definition[Relation::TARGET],
                'schema' => $definition[Relation::SCHEMA],
            ]
        );
    }

    public function collection(
        ORMInterface $orm,
        string $name = null
    ): CollectionFactoryInterface {
        if ($name === null) {
            return $this->defaultCollectionFactory;
        }
        if (array_key_exists($name, $this->collectionFactoryAlias)) {
            return $this->collectionFactoryAlias[$name];
        }
        // Find by interface
        if (\class_exists($name)) {
            foreach ($this->collectionFactoryInterface as $interface => $factory) {
                if (\is_subclass_of($name, $interface, true)) {
                    return $this->collectionFactoryAlias[$name] = $factory->withCollectionClass($name);
                }
            }
        }
        return $this->collectionFactoryAlias[$name] = $this->make($name);
    }

    public function relation(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        string $relation
    ): RelationInterface {
        $relSchema = $schema->defineRelation($role, $relation);
        $type = $relSchema[Relation::TYPE];

        return $this->config->getRelation($type)->resolve(
            $this->factory,
            [
                'orm' => $orm,
                'role' => $role,
                'name' => $relation,
                'target' => $relSchema[Relation::TARGET],
                'schema' => $relSchema[Relation::SCHEMA]
                    + [Relation::LOAD => $relSchema[Relation::LOAD] ?? null]
                    + [Relation::COLLECTION_TYPE => $relSchema[Relation::COLLECTION_TYPE] ?? null],
            ]
        );
    }

    public function database(string $database = null): DatabaseInterface
    {
        return $this->dbal->database($database);
    }

    public function repository(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        ?Select $select
    ): RepositoryInterface {
        $class = $schema->define($role, Schema::REPOSITORY) ?? $this->defaults[Schema::REPOSITORY];

        if (!\is_subclass_of($class, RepositoryInterface::class)) {
            throw new TypecastException($class . ' does not implement ' . RepositoryInterface::class);
        }

        return $this->factory->make(
            $class,
            [
                'select' => $select,
                'orm' => $orm,
                'role' => $role,
            ]
        );
    }

    public function source(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role
    ): SourceInterface {
        $source = $schema->define($role, Schema::SOURCE) ?? $this->defaults[Schema::SOURCE];

        if (!\is_subclass_of($source, SourceInterface::class)) {
            throw new TypecastException($source . ' does not implement ' . SourceInterface::class);
        }

        if ($source !== Source::class) {
            return $this->factory->make($source, ['orm' => $orm, 'role' => $role]);
        }

        $source = new Source(
            $this->database($schema->define($role, Schema::DATABASE)),
            $schema->define($role, Schema::TABLE)
        );

        $scope = $schema->define($role, Schema::SCOPE) ?? $this->defaults[Schema::SCOPE];

        if ($scope === null) {
            return $source;
        }

        if (!\is_subclass_of($scope, ScopeInterface::class)) {
            throw new TypecastException($scope . ' does not implement ' . ScopeInterface::class);
        }

        return $source->withScope(\is_object($scope) ? $scope : $this->factory->make($scope));
    }

    /**
     * Add default classes for resolve
     */
    public function withDefaultSchemaClasses(array $defaults): self
    {
        $clone = clone $this;

        $clone->defaults = $defaults + $this->defaults;

        return $clone;
    }

    public function withCollectionFactory(
        string $alias,
        CollectionFactoryInterface $factory,
        string $interface = null
    ): self {
        $clone = clone $this;
        $clone->collectionFactoryAlias[$alias] = $factory;
        if ($interface !== null) {
            $clone->collectionFactoryInterface[$interface] = $factory;
        }
        return $clone;
    }
}
