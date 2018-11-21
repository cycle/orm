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

    public function init(State $state, array $data): array
    {
        // easy to read huh?
        foreach ($this->relations as $name => $relation) {
            if (array_key_exists($name, $data)) {
                $item = $data[$name];

                if (is_object($item) || is_null($item)) {
                    $state->setRelation($name, $item);
                    continue;
                }

                if (!$relation->isCollection()) {
                    $data[$name] = $relation->init($item);
                    $state->setRelation($name, $data[$name]);
                    continue;
                }

                $relData = $relation->initArray($item);

                $state->setRelation($name, $relData);
                $data[$name] = $relation->wrapCollection($relData);
            }
        }

        return $data;
    }

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
            $state->setVisited($name, true);

            // get the current relation value
            $related = $relation->extract($data[$name] ?? null);
            $oriRelated[$name] = $related;

            // queue needed changes
            $sequence->addCommand(
                $relation->queueDependency($command, $entity, $state, $related, $state->getRelation($name))
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
            $state->setVisited($name, true);

            // get the current relation value
            $related = $relation->extract($data[$name] ?? null);
            $oriRelated[$name] = $related;

            // queue needed changes
            $sequence->addCommand(
                $relation->queueRelation($entity, $state, $related, $state->getRelation($name))
            );

            // update current relation state
            $state->setRelation($name, $related);
        }

        // complete the walkthough sequence
        $sequence->onComplete([$state, 'flushVisited']);

        // reset state and revert relation values
        $sequence->onRollBack(function () use ($state, $oriRelated) {
            $state->flushVisited();
            foreach ($oriRelated as $name => $value) {
                $state->setRelation($name, $value);
            }
        });

        return $sequence;
    }
}