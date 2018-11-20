<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualCommandInterface;

interface RelationInterface
{
    public function isCascade(): bool;

    public function isLeading(): bool;

    public function isCollection(): bool;

    public function queueChange(
        $parent,
        State $state,
        $related,
        $original,
        ContextualCommandInterface $command
    ): CommandInterface;
}