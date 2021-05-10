<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Exception\TypecastException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\ConstrainInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Select\SourceInterface;
use Spiral\Core\Container;
use Spiral\Core\FactoryInterface as CoreFactory;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseProviderInterface;

final class Factory implements FactoryInterface
{
    private RelationConfig $config;
    private CoreFactory $factory;
    private DatabaseProviderInterface $dbal;

    /** @var array<string, string> */
    private array $defaults = [
        Schema::REPOSITORY => Repository::class,
        Schema::SOURCE     => Source::class,
        Schema::MAPPER     => Mapper::class,
        Schema::CONSTRAIN  => null,
    ];

    public function __construct(
        DatabaseProviderInterface $dbal,
        RelationConfig $config = null,
        CoreFactory $factory = null
    ) {
        $this->dbal = $dbal;
        $this->config = $config ?? RelationConfig::getDefault();
        $this->factory = $factory ?? new Container();
    }

    public function make(
        string $alias,
        array $parameters = []
    ) {
        return $this->factory->make($alias, $parameters);
    }

    public function mapper(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role
    ): MapperInterface {
        $class = $schema->define($role, Schema::MAPPER) ?? $this->defaults[Schema::MAPPER];

        if (!is_subclass_of($class, MapperInterface::class)) {
            throw new TypecastException($class . ' does not implement ' . MapperInterface::class);
        }

        return $this->factory->make(
            $class,
            [
                'orm'    => $orm,
                'role'   => $role,
                'schema' => $schema->define($role, Schema::SCHEMA)
            ]
        );
    }

    public function loader(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        string $relation
    ): LoaderInterface {
        $definition = $schema->defineRelation($role, $relation);

        return $this->config->getLoader($definition[Relation::TYPE])->resolve(
            $this->factory,
            [
                'orm'    => $orm,
                'name'   => $relation,
                'target' => $definition[Relation::TARGET],
                'schema' => $definition[Relation::SCHEMA]
            ]
        );
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
                'orm'    => $orm,
                'name'   => $relation,
                'target' => $relSchema[Relation::TARGET],
                'schema' => $relSchema[Relation::SCHEMA] + [Relation::LOAD => $relSchema[Relation::LOAD] ?? null],
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

        if (!is_subclass_of($class, RepositoryInterface::class)) {
            throw new TypecastException($class . ' does not implement ' . RepositoryInterface::class);
        }

        return $this->factory->make(
            $class,
            [
                'select' => $select,
                'orm'    => $orm,
                'role'   => $role,
            ]
        );
    }

    public function source(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role
    ): SourceInterface {
        $source = $schema->define($role, Schema::SOURCE) ?? $this->defaults[Schema::SOURCE];

        if (!is_subclass_of($source, SourceInterface::class)) {
            throw new TypecastException($source . ' does not implement ' . SourceInterface::class);
        }

        if ($source !== Source::class) {
            return $this->factory->make($source, ['orm' => $orm, 'role' => $role]);
        }

        $source = new Source(
            $this->database($schema->define($role, Schema::DATABASE)),
            $schema->define($role, Schema::TABLE)
        );

        $constrain = $schema->define($role, Schema::CONSTRAIN) ?? $this->defaults[Schema::CONSTRAIN];

        if ($constrain === null) {
            return $source;
        }

        if (!is_subclass_of($constrain, ConstrainInterface::class)) {
            throw new TypecastException($constrain . ' does not implement ' . ConstrainInterface::class);
        }

        return $source->withConstrain(is_object($constrain) ? $constrain : $this->factory->make($constrain));
    }

    /**
     * Add default classes for resolve
     */
    public function withDefaultSchemaClasses(array $defaults): FactoryInterface
    {
        $clone = clone $this;

        $clone->defaults = $defaults + $this->defaults;

        return $clone;
    }
}
