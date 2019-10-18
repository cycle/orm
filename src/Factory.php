<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\LoaderInterface;
use Psr\Container\ContainerInterface;
use Spiral\Core\Container;
use Spiral\Core\FactoryInterface as CoreFactory;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseProviderInterface;

final class Factory implements FactoryInterface
{
    /** @var RelationConfig */
    private $config;

    /** @var CoreFactory */
    private $factory;

    /** @var ContainerInterface */
    private $container;

    /** @var DatabaseProviderInterface */
    private $dbal;

    /**
     * @param DatabaseProviderInterface $dbal
     * @param RelationConfig            $config
     * @param CoreFactory|null          $factory
     * @param ContainerInterface|null   $container
     */
    public function __construct(
        DatabaseProviderInterface $dbal,
        RelationConfig $config = null,
        CoreFactory $factory = null,
        ContainerInterface $container = null
    ) {
        $this->dbal = $dbal;
        $this->config = $config ?? RelationConfig::getDefault();
        $this->factory = $factory ?? new Container();
        $this->container = $container ?? new Container();
    }

    /**
     * @inheritdoc
     */
    public function make(string $alias, array $parameters = [])
    {
        return $this->factory->make($alias, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function mapper(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role
    ): MapperInterface {
        $class = $schema->define($role, Schema::MAPPER) ?? Mapper::class;

        return $this->factory->make($class, [
            'orm'    => $orm,
            'role'   => $role,
            'schema' => $schema->define($role, Schema::SCHEMA)
        ]);
    }

    /**
     * @inheritdoc
     */
    public function loader(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        string $relation
    ): LoaderInterface {
        $schema = $schema->defineRelation($role, $relation);

        return $this->config->getLoader($schema[Relation::TYPE])->resolve($this->factory, [
            'orm'    => $orm,
            'name'   => $relation,
            'target' => $schema[Relation::TARGET],
            'schema' => $schema[Relation::SCHEMA]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function relation(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        string $relation
    ): RelationInterface {
        $relSchema = $schema->defineRelation($role, $relation);
        $type = $relSchema[Relation::TYPE];

        return $this->config->getRelation($type)->resolve($this->factory, [
            'orm'    => $orm,
            'name'   => $relation,
            'target' => $relSchema[Relation::TARGET],
            'schema' => $relSchema[Relation::SCHEMA] + [Relation::LOAD => $relSchema[Relation::LOAD] ?? null],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function database(string $database = null): DatabaseInterface
    {
        return $this->dbal->database($database);
    }
}
