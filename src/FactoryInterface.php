<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\SourceInterface;
use Spiral\Core\FactoryInterface as CoreFactory;
use Spiral\Database\DatabaseProviderInterface;

/**
 * Must provide access to generic DI.
 */
interface FactoryInterface extends DatabaseProviderInterface, CoreFactory
{
    public const
        PARENT_LOADER = '::parent::',
        CHILD_LOADER = '::child::';

    /**
     * Create mapper associated with given role.
     */
    public function mapper(
        ORMInterface $orm,
        string $role
    ): MapperInterface;

    /**
     * Create loader associated with specific entity and relation.
     */
    public function loader(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        string $relation
    ): LoaderInterface;


    /**
     * Create repository associated with given role,
     */
    public function repository(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        ?Select $select
    ): RepositoryInterface;

    /**
     * Create source associated with given role
     */
    public function source(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role
    ): SourceInterface;

    public function collection(
        ORMInterface $orm,
        string $type = null,
        array $options = null
    ): CollectionFactoryInterface;

    /**
     * Create relation associated with specific entity and relation.
     */
    public function relation(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        string $relation
    ): RelationInterface;
}
