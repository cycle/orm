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

        foreach ($this->dependencies as $name => $relation) {
            if (!$relation->isCascade()) {
                continue;
            }

            $sequence->addCommand(
                $relation->queueDependency(
                    $command,
                    $entity,
                    $state,
                    $data[$name] ?? null,
                    $state->getRelation($name)
                )
            );
        }

        $sequence->addPrimary($command);

        foreach ($this->relations as $name => $relation) {
            if (!$relation->isCascade() || $state->visited($name)) {
                continue;
            }
            $state->setVisited($name, true);

            $sequence->addCommand(
                $relation->queueRelation(
                    $entity,
                    $state,
                    $data[$name] ?? null,
                    $state->getRelation($name),
                    $command
                )
            );
        }

        $sequence->onComplete([$state, 'flushVisited']);
        $sequence->onRollBack([$state, 'flushVisited']);

        return $sequence;
    }
}