<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Control\ContextualSequence;

/**
 * Generates set of linked commands required to persis or delete given dependency graph. Each
 * RelationMap is specific to one instance class (type).
 */
final class RelationMap
{
    /**
     * @invisible
     * @var ORMInterface
     */
    private $orm;

    /** @var RelationInterface[] */
    private $relations = [];

    /** @var DependencyInterface[] */
    private $dependencies = [];

    /**
     * @param ORMInterface $orm
     * @param array        $relations
     */
    public function __construct(ORMInterface $orm, array $relations)
    {
        $this->orm = $orm;
        $this->relations = $relations;

        foreach ($this->relations as $name => $relation) {
            if ($relation instanceof DependencyInterface) {
                $this->dependencies[$name] = $relation;
            }
        }
    }

    /**
     * Init relation data in entity data and entity state.
     *
     * @param State $state
     * @param array $data
     * @return array
     */
    public function init(State $state, array $data): array
    {
        foreach ($this->relations as $name => $relation) {
            if (!array_key_exists($name, $data)) {
                if ($state->hasRelation($name)) {
                    continue;
                }

                $data[$name] = $relation->initPromise($state, $data);
                continue;
            }

            $item = $data[$name];
            if (is_object($item) || is_null($item)) {
                // cyclic initialization
                $state->setRelation($name, $item);
                continue;
            }

            // init relation for the entity and for state and the same time
            list($data[$name], $item) = $relation->init($item);

            // store current state of relation
            $state->setRelation($name, $item);
        }

        return $data;
    }

    /**
     * Generate set of commands required to store the entity and it's relations.
     *
     * @param object              $entity
     * @param array               $data
     * @param State               $state
     * @param ContextualInterface $command
     * @return ContextualInterface
     */
    public function queueRelations(
        $entity,
        array $data,
        State $state,
        ContextualInterface $command
    ): ContextualInterface {
        $sequence = new ContextualSequence();
        $oriRelated = [];

        // queue all "left" graph branches
        foreach ($this->dependencies as $name => $relation) {
            if (!$relation->isCascade() || $state->visited($name)) {
                continue;
            }
            $state->markVisited($name);

            // get the current relation value
            $related = $relation->extract($data[$name] ?? null);
            $oriRelated[$name] = $related;

            // queue needed changes
            $sequence->addCommand(
                $relation->queueRelation($command, $entity, $state, $related,
                    $state->getRelation($name))
            );

            // update current relation state
            $state->setRelation($name, $related);
        }

        // queue target entity
        $sequence->addPrimary($command);

        // queue all "right" graph branches
        foreach ($this->relations as $name => $relation) {
            if (!$relation->isCascade() || $state->visited($name)) {
                continue;
            }
            $state->markVisited($name);

            // get the current relation value
            $related = $relation->extract($data[$name] ?? null);
            $oriRelated[$name] = $related;

            // queue needed changes
            $sequence->addCommand(
                $relation->queueRelation($command, $entity, $state, $related,
                    $state->getRelation($name))
            );

            // update current relation state
            $state->setRelation($name, $related);
        }

        // complete the walk-though sequence
        $sequence->onComplete([$state, 'resetVisited']);

        // reset state and revert relation values
        $sequence->onRollBack(function () use ($state, $oriRelated) {
            $state->resetVisited();
            foreach ($oriRelated as $name => $value) {
                $state->setRelation($name, $value);
            }
        });

        return $sequence;
    }
}