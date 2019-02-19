<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM;

use Spiral\Core\Container;
use Spiral\Core\FactoryInterface as CoreFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Exception\FactoryException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Mapper\MapperInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Select\SourceFactoryInterface;
use Cycle\ORM\Select\SourceInterface;
use Spiral\Database\DatabaseManager;

class Factory implements FactoryInterface, SourceFactoryInterface
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
    public function withSchema(ORMInterface $orm, SchemaInterface $schema): FactoryInterface
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
        $class = $this->getSchema()->define($role, Schema::MAPPER) ?? Mapper::class;
        return $this->factory->make($class, [
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
     * @inheritdoc
     */
    public function getSource(string $role): SourceInterface
    {
        $class = $this->schema->define($role, Schema::SOURCE) ?? Source::class;

        /** @var SourceInterface $source */
        $source = $this->factory->make($class, [
            'database' => $this->dbal->database($this->schema->define($role, Schema::DATABASE)),
            'table'    => $this->schema->define($role, Schema::TABLE),
        ]);

        $constrain = $this->schema->define($role, Schema::CONSTRAIN);
        if ($constrain !== null) {
            $source = $source->withConstrain($this->factory->make($constrain));
        }

        return $source;
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