<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

/**
 * @internal
 */
final class SpecialValue
{
    private static self $empty;

    public static function init(): void
    {
        self::$empty = new self();
    }

    /**
     * An empty value that is used to indicate that a value has not been set.
     */
    public static function notSet(): self
    {
        return self::$empty;
    }

    public static function isNotSet(mixed $value): bool
    {
        return $value === self::$empty;
    }

    public static function isEmpty(mixed $value): bool
    {
        return $value === self::$empty || $value === null || $value === [];
    }
}

SpecialValue::init();
