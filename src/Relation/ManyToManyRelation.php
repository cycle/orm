<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;


use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCommandInterface;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\State;

class ManyToManyRelation extends AbstractRelation
{
    public const COLLECTION = true;

    public function queueChange(
        $parent,
        State $state,
        $related,
        ContextCommandInterface $command
    ): CommandInterface {

        // store or not to store?
        // cascade can only update

        return new NullCommand();
    }
}