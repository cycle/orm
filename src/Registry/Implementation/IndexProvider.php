<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry\Implementation;

use Cycle\ORM\Registry\IndexProviderInterface;
use Cycle\ORM\SchemaInterface;

use const SORT_REGULAR;

/**
 * @internal
 */
final class IndexProvider implements IndexProviderInterface
{
    private array $indexes = [];

    public function __construct(
        private SchemaInterface $schema,
    ) {
    }

    public function getIndexes(string $entity): array
    {
        if (isset($this->indexes[$entity])) {
            return $this->indexes[$entity];
        }

        $pk = $this->schema->define($entity, SchemaInterface::PRIMARY_KEY);
        $keys = $this->schema->define($entity, SchemaInterface::FIND_BY_KEYS) ?? [];

        return $this->indexes[$entity] = \array_unique(\array_merge([$pk], $keys), SORT_REGULAR);
    }
}
