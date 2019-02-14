<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema;

class Packer
{
    /** @var array */
    private $schema = [];

    /**
     * Get packed schema result.
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->schema;
    }
}