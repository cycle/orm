<?php

declare(strict_types=1);

namespace Cycle\ORM\Service\Implementation;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Service\RepositoryProviderInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;

/**
 * @internal
 */
final class RepositoryProvider implements RepositoryProviderInterface
{
    /** @var array<non-empty-string, RepositoryInterface> */
    private array $repositories = [];

    public function __construct(
        private ORMInterface $orm,
        private SourceProviderInterface $sourceProvider,
        private SchemaInterface $schema,
        private FactoryInterface $factory
    ) {
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
}
