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
     * The rules can be cleaned there and returned to the next typecast.
     *
     * @param array<non-empty-string, mixed> $rules
     *
     * @return array<non-empty-string, mixed> Cleaned rules
     */
    public function setRules(array $rules): array;

    /**
     * Typecast key-values into an internal representation.
     *
     * @throws TypecastException
     */
    public function cast(array $values): array;
}
