<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\DependencyInterface;
use Spiral\ORM\Exception\Relation\NullException;
use Spiral\ORM\Relation;
use Spiral\ORM\StateInterface;

class BelongsToRelation extends AbstractRelation implements DependencyInterface
{
    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $command,
        $entity,
        StateInterface $state,
        $related,
        $original
    ): CommandInterface {
        if (is_null($related) && !$this->define(Relation::NULLABLE)) {
            throw new NullException("Relation {$this} can not be null");
        }

        if (is_null($related)) {
            $command->setContext($this->innerKey, null);

            return new NullCommand();
        }

        $relStore = $this->orm->queueStore($related);
        $relState = $this->getState($related);

        $this->promiseContext($command, $relState, $this->outerKey, $state, $this->innerKey);

        // todo: morph key

        return $relStore;
    }
}