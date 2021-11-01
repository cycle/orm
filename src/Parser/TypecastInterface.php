<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\TypecastException;

/**
 * Typecaster provides ability to cast column values into their internal representation.
 */
interface TypecastInterface
{
    /**
     * Typecast key-values into internal representation.
     *
     * @throws TypecastException
     */
    public function cast(array $values): array;
}
