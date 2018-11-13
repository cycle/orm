<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CommandPromiseInterface;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\State;

class BelongsToRelation extends AbstractRelation
{
    // todo: move to the strategy
    public function queueChange(
        $parent,
        State $state,
        CommandPromiseInterface $command
    ): CommandInterface {
        return new NullCommand();

    }
}