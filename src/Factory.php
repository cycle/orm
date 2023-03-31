<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Exception\FactoryTypecastException;
use Cycle\ORM\Exception\TypecastException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Parser\CompositeTypecast;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\Loader\ParentLoader;
use Cycle\ORM\Select\Loader\SubclassLoader;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\ScopeInterface;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Select\SourceInterface;
use Spiral\Core\Container;
use Spiral\Core\FactoryInterface as CoreFactory;

final class Factory implements FactoryInterface
{
    private RelationConfig $config;
    private CoreFactory $factory;

    /** @var array<int, string> */
    private array $defaults = [
        SchemaInterface::REPOSITORY => Repository::class,
        SchemaInterface::SOURCE => Source::class,
        SchemaInterface::MAPPER => Mapper::class,
        SchemaInterface::SCOPE => null,
        SchemaInterface::TYPECAST_HANDLER => null,
    ];

    /** @var array<string, CollectionFactoryInterface> */
    private array $collectionFactoryAlias = [];

    /**
     * @var array<string, CollectionFactoryInterface>
     *
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

    public function typecast(SchemaInterface $schema, DatabaseInterface $database, string $role): ?TypecastInterface
    {
        // Get parent's typecast
        $parent = $schema->define($role, SchemaInterface::PARENT);
        $parentHandler = $parent === null ? null : $this->typecast($schema, $database, $parent);
        $handlers = [];

        // Schema's `typecast` option
        $rules = (array)$schema->define($role, SchemaInterface::TYPECAST);
        $handler = $schema->define($role, SchemaInterface::TYPECAST_HANDLER)
            ?? $this->defaults[SchemaInterface::TYPECAST_HANDLER];

        // Create basic typecast implementation
        try {
            if ($handler === null) {
                if (!$rules) {
                    return $parentHandler;
                }

                $handlers[] = new Typecast($database);
            } elseif (\is_array($handler)) { // We need to use composite typecast for array
                foreach ($handler as $type) {
                    $handlers[] = $this->makeTypecastHandler($type, $database, $schema, $role);
                }
            } else {
                $handlers[] = $this->makeTypecastHandler($handler, $database, $schema, $role);
            }
        } catch (\Throwable $e) {
            throw new FactoryTypecastException(
                message: \sprintf(
                    'Bad typecast handler declaration for the `%s` role. %s',
                    $role,
                    $e->getMessage()
                ),
                code: $e->getCode(),
                previous: $e,
            );
        }
        $handler = count($handlers) === 1 ? reset($handlers) : new CompositeTypecast(...$handlers);
        $handler->setRules($rules);
        return $parentHandler === null
            ? $handler
            : new CompositeTypecast($parentHandler, $handler);
    }

    public function mapper(ORMInterface $orm, string $role): MapperInterface
    {
        $schema = $orm->getSchema();
        $class = $schema->define($role, SchemaInterface::MAPPER) ?? $this->defaults[SchemaInterface::MAPPER];

        if (!\is_subclass_of($class, MapperInterface::class)) {
            throw new TypecastException(sprintf('%s does not implement %s.', $class, MapperInterface::class));
        }

        return $this->factory->make(
            $class,
            [
                'orm' => $orm,
                'role' => $role,
                'schema' => $schema->define($role, SchemaInterface::SCHEMA),
            ]
        );
    }

    public function loader(
        SchemaInterface $schema,
        SourceProviderInterface $sourceProvider,
        string $role,
        string $relation
    ): LoaderInterface {
        if ($relation === self::PARENT_LOADER) {
            $parent = $schema->define($role, SchemaInterface::PARENT);
            return new ParentLoader($schema, $sourceProvider, $this, $role, $parent);
        }
        if ($relation === self::CHILD_LOADER) {
            $parent = $schema->define($role, SchemaInterface::PARENT);
            return new SubclassLoader($schema, $sourceProvider, $this, $parent, $role);
        }
        $definition = $schema->defineRelation($role, $relation);

        return $this->config->getLoader($definition[Relation::TYPE])->resolve(
            $this->factory,
            [
                'ormSchema' => $schema,
                'sourceProvider' => $sourceProvider,
                'factory' => $this,
                'role' => $role,
                'name' => $relation,
                'target' => $definition[Relation::TARGET],
                'schema' => $definition[Relation::SCHEMA],
            ]
        );
    }

    public function collection(
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
        return $this->collectionFactoryAlias[$name] = $this->factory->make($name);
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
        $class = $schema->define($role, SchemaInterface::REPOSITORY) ?? $this->defaults[SchemaInterface::REPOSITORY];

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
        SchemaInterface $schema,
        string $role
    ): SourceInterface {
        /** @var class-string<SourceInterface> $source */
        $source = $schema->define($role, SchemaInterface::SOURCE) ?? $this->defaults[SchemaInterface::SOURCE];

        if (!\is_subclass_of($source, SourceInterface::class)) {
            throw new TypecastException($source . ' does not implement ' . SourceInterface::class);
        }

        $table = $schema->define($role, SchemaInterface::TABLE);
        $database = $this->database($schema->define($role, SchemaInterface::DATABASE));

        $source = $source !== Source::class
            ? $this->factory->make($source, ['role' => $role, 'table' => $table, 'database' => $database])
            : new Source($database, $table);

        /** @var class-string<ScopeInterface>|ScopeInterface|null $scope */
        $scope = $schema->define($role, SchemaInterface::SCOPE) ?? $this->defaults[SchemaInterface::SCOPE];

        if ($scope === null) {
            return $source;
        }

        if (!\is_subclass_of($scope, ScopeInterface::class)) {
            throw new TypecastException(sprintf('%s does not implement %s.', $scope, ScopeInterface::class));
        }

        return $source->withScope(\is_object($scope) ? $scope : $this->factory->make($scope));
    }

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
        $interface = $interface ?? $factory->getInterface();

        $clone->collectionFactoryAlias[$alias] = $factory;
        if ($interface !== null) {
            $clone->collectionFactoryInterface[$interface] = $factory;
        }

        return $clone;
    }

    /**
     * Make typecast handler from giver string or object
     *
     * @return TypecastInterface
     */
    private function makeTypecastHandler(
        string|TypecastInterface $handler,
        DatabaseInterface $database,
        SchemaInterface $schema,
        string $role
    ): TypecastInterface {
        // If handler is an object we don't need to use factory, we should return it as is
        if (is_object($handler)) {
            return $handler;
        }

        return $this->factory->make($handler, [
            'database' => $database,
            'schema' => $schema,
            'role' => $role,
        ]);
    }
}
