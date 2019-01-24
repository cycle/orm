<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Parser;

use Spiral\Cycle\Exception\TypecastException;

/**
 * Typecaster provides ability to cast column values into their internal representation.
 */
interface TypecasterInterface
{
    /**
     * Typecast key-values into internal representation.
     *
     * @param array $values
     * @return array
     *
     * @throws TypecastException
     */
    public function cast(array $values): array;
}