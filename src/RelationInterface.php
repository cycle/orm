<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CommandPromiseInterface;

interface RelationInterface
{
    public function queueChange(
        $parent,
        State $state,
        CommandPromiseInterface $command
    ): CommandInterface;
}