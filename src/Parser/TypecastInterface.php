<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\TypecastException;

/**
 * Typecaster provides an ability to cast entity column values into their internal representation.
 */
interface TypecastInterface
{
    /**
     * Passes columns rules to a typecast object.
     *
     * @param array<non-empty-string, mixed> $rules
     */
    public function setRules(array &$rules): void;

    /**
     * Typecast key-values into an internal representation.
     *
     * @throws TypecastException
     */
    public function cast(array $values): array;
}
