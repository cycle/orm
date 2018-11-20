<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;

interface RelationInterface
{
    public function isCascade(): bool;

    public function isCollection(): bool;

    public function queueRelation($entity, State $state, $related, $original): CommandInterface;
}