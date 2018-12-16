<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

use Spiral\Core\Container;
use Spiral\Core\FactoryInterface as CoreFactory;
use Spiral\Cycle\Config\RelationConfig;
use Spiral\Cycle\Exception\FactoryException;
use Spiral\Cycle\Mapper\MapperInterface;
use Spiral\Cycle\Relation\RelationInterface;
use Spiral\Cycle\Select\LoaderInterface;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager;

class Factory implements FactoryInterface
{
    /** @var RelationConfig */
    private $config;

    /** @var CoreFactory */
    private $factory;

    /** @var DatabaseManager */
    private $dbal;

    /** @var ORMInterface */
    private $orm;

    /** @var SchemaInterface */
    private $schema;

    /**
     * @param DatabaseManager  $dbal
     * @param RelationConfig   $config
     * @param CoreFactory|null $factory
     */
    public function __construct(DatabaseManager $dbal, RelationConfig $config, CoreFactory $factory = null)
    {
        $this->dbal = $dbal;
        $this->config = $config;
        $this->factory = $factory ?? new Container();
    }

    /**
     * @inheritdoc
     */
    public function database(string $name = null): DatabaseInterface
    {
        return $this->dbal->database($name);
    }

    /**
     * @inheritdoc
     */
    public function withContext(ORMInterface $orm, SchemaInterface $schema): FactoryInterface
    {
        $factory = clone $this;
        $factory->orm = $orm;
        $factory->schema = $schema;

        return $factory;
    }

    /**
     * @inheritdoc
     */
    public function mapper(string $role): MapperInterface
    {
        return $this->factory->make($this->getSchema()->define($role, Schema::MAPPER), [
            'orm'    => $this->orm,
            'role'   => $role,
            'schema' => $this->getSchema()->define($role, Schema::SCHEMA)
        ]);
    }

    /**
     * @inheritdoc
     */
    public function loader(string $class, string $relation): LoaderInterface
    {
        $schema = $this->getSchema()->defineRelation($class, $relation);

        return $this->config->getLoader($schema[Relation::TYPE])->resolve($this->factory, [
            'orm'    => $this->orm,
            'name'   => $relation,
            'target' => $schema[Relation::TARGET],
            'schema' => $schema[Relation::SCHEMA]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function relation(string $class, string $relation): RelationInterface
    {
        $schema = $this->getSchema()->defineRelation($class, $relation);
        $type = $schema[Relation::TYPE];

        return $this->config->getRelation($type)->resolve($this->factory, [
            'orm'    => $this->orm,
            'name'   => $relation,
            'target' => $schema[Relation::TARGET],
            'schema' => $schema[Relation::SCHEMA]
        ]);
    }

    /**
     * @return SchemaInterface
     *
     * @throws FactoryException
     */
    protected function getSchema(): SchemaInterface
    {
        if (empty($this->schema)) {
            throw new FactoryException("Factory does not have associated schema");
        }

        return $this->schema;
    }
}