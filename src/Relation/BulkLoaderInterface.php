<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

/**
 * Bulk relation loader allows to collect a set of entities and load their relations in bulk.
 */
interface BulkLoaderInterface
{
    /**
     * Collect entities for bulk relation loading.
     *
     * @param object ...$entities Entities to collect
     * @psalm-immutable
     */
    public function collect(object ...$entities): RelationLoaderInterface;
}
