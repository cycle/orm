<?php

declare(strict_types=1);

namespace Cycle\ORM;

/**
 * ORM behavior options.
 */
final class Options
{
    /**
     * @readonly
     * @note will be set to TRUE in the next major version.
     */
    public bool $ignoreUninitializedRelations = false;

    /**
     * @readonly
     * @note will be set to TRUE in the next major version.
     */
    public bool $groupByToDeduplicate = false;

    /**
     * If TRUE, ORM will ignore relations on uninitialized Entity properties.
     * In this case, `unset($entity->relation)` will not change the relation when saving,
     * and it will hydrate it if the relation is loaded in the query.
     *
     * If FALSE, uninitialized properties will be treated as NULL (an empty collection or empty value).
     * `unset($entity->relation)` will lead to a change in the relation
     * (removing the link with another entity or entities).
     */
    public function withIgnoreUninitializedRelations(bool $value): static
    {
        $clone = clone $this;
        $clone->ignoreUninitializedRelations = $value;
        return $clone;
    }

    /**
     * If TRUE, ORM will use GROUP BY to deduplicate entities in Select queries in cases where
     * `limit` and `offset` with JOINs are used.
     *
     * If FALSE, ORM will not use GROUP BY, which may lead wrong results in cases where
     * `limit` and `offset` are used with JOINs.
     */
    public function withGroupByToDeduplicate(bool $value): static
    {
        $clone = clone $this;
        $clone->groupByToDeduplicate = $value;
        return $clone;
    }
}
