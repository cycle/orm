<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\ContextCarrierInterface;
use Spiral\ORM\Command\Branch\PrimarySequence;
use Spiral\ORM\Command\Branch\Sequence;

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

                list($data[$name], $orig) = $relation->initPromise($state, $data);
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
     * Generate set of commands required to store the entity and it's relations.
     *
     * @param object                  $parentEntity
     * @param array                   $data
     * @param Node                    $parentPoint
     * @param ContextCarrierInterface $command
     * @return ContextCarrierInterface
     */
    public function queueRelations(
        $parentEntity,
        array $data,
        Node $parentPoint,
        ContextCarrierInterface $command
    ): ContextCarrierInterface {
        $sequence = new PrimarySequence();

        // queue all "left" graph branches
        foreach ($this->dependencies as $name => $relation) {
            if (!$relation->isCascade() || $parentPoint->getState()->visited($name)) {
                continue;
            }

            $this->queueRelation($sequence, $parentEntity, $data, $parentPoint, $command, $relation, $name);
        }

        // queue target entity
        $sequence->addPrimary($command);

        // queue all "right" graph branches
        foreach ($this->relations as $name => $relation) {
            if (!$relation->isCascade() || $parentPoint->getState()->visited($name)) {
                continue;
            }

            $this->queueRelation($sequence, $parentEntity, $data, $parentPoint, $command, $relation, $name);
        }

        if (count($sequence) === 1) {
            return current($sequence->getCommands());
        }

        return $sequence;
    }

    /**
     * Queue relation and return related object.
     *
     * @param Sequence                $parentSequence
     * @param object                  $parent
     * @param array                   $data
     * @param Node                    $parentPoint
     * @param ContextCarrierInterface $command
     * @param RelationInterface       $relation
     * @param string                  $name
     */
    private function queueRelation(
        Sequence $parentSequence,
        $parent,
        array $data,
        // move
        Node $parentPoint,
        ContextCarrierInterface $command,
        RelationInterface $relation,
        string $name
    ) {
        // get the current relation value
        $related = $relation->extract($data[$name] ?? null);
        $original = $parentPoint->getRelation($name);

        // indicate that branch has been calculated
        $parentPoint->getState()->markVisited($name);

        // no changes in non changed promised relation
        if ($related instanceof PromiseInterface && $related === $original) {
            return;
        }

        $relStore = $relation->queueRelation($command, $parent, $parentPoint, $related, $original);

        if ($relStore instanceof Sequence && count($relStore) === 1) {
            $relStore = current($relStore->getCommands());
        }

        // queue needed changes
        $parentSequence->addCommand($relStore);

        // update current relation state
        $parentPoint->setRelation($name, $related);

        return;
    }
}