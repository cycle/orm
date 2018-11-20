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
     * @param array $where
     */
    public function setWhere(array $where);

    /**
     * @return array
     */
    public function getWhere(): array;
}