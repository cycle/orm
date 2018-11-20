<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;

interface DependencyInterface extends RelationInterface
{
    public function queueDependency(
        ContextualInterface $command,
        $entity,
        State $state,
        $related,
        $original
    ): CommandInterface;
}