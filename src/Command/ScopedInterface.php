<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

interface ScopedInterface extends CommandInterface
{
    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setWhere(string $key, $value);

    /**
     * @return array
     */
    public function getWhere(): array;
}