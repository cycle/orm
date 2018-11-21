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
use Spiral\ORM\State;

class BelongsToRelation extends AbstractRelation implements DependencyInterface
{
    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $command,
        $entity,
        State $state,
        $related,
        $original
    ): CommandInterface {
        // todo: into null ?
        if ($related === null && !$this->define(Relation::NULLABLE)) {
            throw new NullException(
                "Relation `{$this->class}`.`{$this->relation}` can not be null"
            );
        }

        if (is_null($related)) {
            $command->setContext($this->define(Relation::INNER_KEY), null);

            return new NullCommand();
        }

        $relStore = $this->orm->getMapper($related)->queueStore($related);
        $relState = $this->getState($related);

        $this->promiseContext(
            $command,
            $relState,
            $this->define(Relation::OUTER_KEY),
            $state,
            $this->define(Relation::INNER_KEY)
        );

        // todo: morph key

        return $relStore;
    }
}