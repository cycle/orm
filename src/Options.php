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
     */
    public bool $ignoreUninitializedRelations = true;

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
}
