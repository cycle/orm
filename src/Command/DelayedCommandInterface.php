<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

interface DelayedCommandInterface extends CommandInterface
{
    //todo: phpdoc
    public function isDelayed(): bool;
}