<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\Core\Container;
use Spiral\Core\FactoryInterface as CoreFactory;
use Spiral\ORM\Config\RelationConfig;
use Spiral\ORM\Exception\FactoryException;

class Factory implements FactoryInterface
{
    /** @var RelationConfig */
    private $config;

    /** @var CoreFactory */
    private $factory;

    /** @var ORMInterface */
    private $orm;

    /** @var SchemaInterface */
    private $schema;

    /**
     * @param RelationConfig   $config
     * @param CoreFactory|null $factory
     */
    public function __construct(RelationConfig $config, CoreFactory $factory = null)
    {
        $this->config = $config;
        $this->factory = $factory ?? new Container();
    }

    /**
     * @inheritdoc
     */
    public function withConfigured(ORMInterface $orm, SchemaInterface $schema): FactoryInterface
    {
        $factory = clone $this;
        $factory->orm = $orm;
        $factory->schema = $schema;

        return $factory;
    }

    /**
     * @inheritdoc
     */
    public function mapper(string $class): MapperInterface
    {
        return $this->factory->make($this->getSchema()->define($class, Schema::MAPPER), [
            'orm'    => $this->orm,
            'class'  => $class,
            'schema' => $this->getSchema()->define($class, Schema::SCHEMA)
        ]);
    }

    /**
     * @inheritdoc
     */
    public function loader(string $class, string $relation): LoaderInterface
    {
        $schema = $this->getSchema()->defineRelation($class, $relation);

        return $this->config->getLoader($schema[Relation::TYPE])->resolve(
            $this->factory,
            [
                'orm'      => $this->orm,
                'class'    => $schema[Relation::TARGET],
                'relation' => $relation,
                'schema'   => $schema[Relation::SCHEMA]
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function relation(string $class, string $relation): RelationInterface
    {
        $schema = $this->getSchema()->defineRelation($class, $relation);
        $type = $schema[Relation::TYPE];

        return $this->config->getRelation($type)->resolve($this->factory, [
            'orm'      => $this->orm,
            'class'    => $schema[Relation::TARGET],
            'relation' => $relation,
            'schema'   => $schema[Relation::SCHEMA]
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