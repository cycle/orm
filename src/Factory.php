<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM;

use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\LoaderInterface;
use Psr\Container\ContainerInterface;
use Spiral\Core\Container;
use Spiral\Core\FactoryInterface as CoreFactory;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager;

class Factory implements FactoryInterface
{
    /** @var RelationConfig */
    private $config;

    /** @var CoreFactory */
    private $factory;

    /** @var ContainerInterface */
    private $container;

    /** @var DatabaseManager */
    private $dbal;

    /**
     * @param DatabaseManager         $dbal
     * @param RelationConfig          $config
     * @param CoreFactory|null        $factory
     * @param ContainerInterface|null $container
     */
    public function __construct(
        DatabaseManager $dbal,
        RelationConfig $config,
        CoreFactory $factory = null,
        ContainerInterface $container = null
    ) {
        $this->dbal = $dbal;
        $this->config = $config;
        $this->factory = $factory ?? new Container();
        $this->container = $container ?? new Container();
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        return $this->container->get($id);
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
            'schema' => $relSchema[Relation::SCHEMA]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function database(string $database): DatabaseInterface
    {
        return $this->dbal->database($database);
    }
}