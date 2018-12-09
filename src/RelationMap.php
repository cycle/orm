<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\Branch\ContextSequence;
use Spiral\ORM\Command\Branch\Sequence;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface;

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
     * @param Node  $state
     * @param array $data
     * @return array
     */
    public function init(Node $state, array $data): array
    {
        foreach ($this->relations as $name => $relation) {
            if (!array_key_exists($name, $data)) {
                if ($state->hasRelation($name)) {
                    // todo: do i need it here?
                    continue;
                }

                list($data[$name], $orig) = $relation->initPromise($state);
                $state->setRelation($name, $orig);
                continue;
            }

            $item = $data[$name];
            if (is_object($item) || is_null($item)) {
                // cyclic initialization
                $state->setRelation($name, $item);
                continue;
            }

            // init relation for the entity and for state and the same time
            list($data[$name], $orig) = $relation->init($item);
            $state->setRelation($name, $orig);
        }

        return $data;
    }

    /**
     * Queue entity relations.
     *
     * @param ContextCarrierInterface $parentStore
     * @param object                  $parentEntity
     * @param Node                    $parentNode
     * @param array                   $parentData
     * @return ContextCarrierInterface
     */
    public function queueRelations(
        ContextCarrierInterface $parentStore,
        $parentEntity,
        Node $parentNode,
        array $parentData
    ): ContextCarrierInterface {

        $state = $parentNode->getState();

        $sequence = new ContextSequence();

        // queue all "left" graph branches
        foreach ($this->dependencies as $name => $relation) {
            if (!$relation->isCascade() || $parentNode->getState()->visited($name)) {
                continue;
            }
            $state->markVisited($name);

            $command = $this->queueRelation(
                $parentStore,
                $parentEntity,
                $parentNode,
                $relation,
                $relation->extract($parentData[$name] ?? null),
                $parentNode->getRelation($name)
            );

            if ($command !== null) {
                $sequence->addCommand($command);
            }
        }

        // queue target entity
        $sequence->addPrimary($parentStore);

        // queue all "right" graph branches
        foreach ($this->relations as $name => $relation) {
            if (!$relation->isCascade() || $parentNode->getState()->visited($name)) {
                continue;
            }
            $state->markVisited($name);

            $command = $this->queueRelation(
                $parentStore,
                $parentEntity,
                $parentNode,
                $relation,
                $relation->extract($parentData[$name] ?? null),
                $parentNode->getRelation($name)
            );

            if ($command !== null) {
                $sequence->addCommand($command);
            }
        }

        if (count($sequence) === 1) {
            return current($sequence->getCommands());
        }

        return $sequence;
    }

    /**
     * Queue the relation.
     *
     * @param ContextCarrierInterface $parentStore
     * @param object                  $parentEntity
     * @param Node                    $parentNode
     * @param RelationInterface       $relation
     * @param mixed                   $related
     * @param mixed                   $original
     * @return CommandInterface|null
     */
    private function queueRelation(
        ContextCarrierInterface $parentStore,
        $parentEntity,
        Node $parentNode,
        RelationInterface $relation,
        $related,
        $original
    ): ?CommandInterface {
        if ($related instanceof PromiseInterface && $related === $original) {
            // no changes in non changed promised relation
            return null;
        }

        $relStore = $relation->queueRelation(
            $parentStore,
            $parentEntity,
            $parentNode,
            $related,
            $original
        );

        if ($relStore instanceof Sequence && count($relStore) === 1) {
            // simplify the branch
            $relStore = current($relStore->getCommands());
        }

        // update current relation state
        $parentNode->getState()->setRelation($relation->getName(), $related);

        return $relStore;
    }
}