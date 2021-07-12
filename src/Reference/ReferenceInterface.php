<?php

declare(strict_types=1);

namespace Cycle\ORM\Reference;

/**
 * Reference points to a remote entity.
 */
interface ReferenceInterface
{
    /**
     * Entity role associated with the promise.
     */
    public function getRole(): string;

    /**
     * Data to unique identify the entity. In most of cases simply contain outer key name (primary key) and
     * it's value.
     */
    public function getScope(): array;

    public function hasValue(): bool;

    /**
     * @param mixed $value
     */
    public function setValue($value): void;

    /**
     * @return mixed
     */
    public function getValue();
}
