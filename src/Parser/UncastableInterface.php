<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\TypecastException;

interface UncastableInterface extends TypecastInterface
{
    /**
     * Uncast key-values into a database representation.
     *
     * @throws TypecastException
     */
    public function uncast(array $data): array;
}
