<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\Control\PrimarySequence;
use Spiral\ORM\Command\Control\Sequence;

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
     * @param Point $state
     * @param array $data
     * @return array
     */
    public function init(Point $state, array $data): array
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
     * @param object           $entity
     * @param array            $data
     * @param Point            $state
     * @param CarrierInterface $command
     * @return CarrierInterface
     */
    public function queueRelations(
        $entity,
        array $data,
        Point $state,
        CarrierInterface $command
    ): CarrierInterface {
        $sequence = new PrimarySequence();
        $origRelated = [];

        // queue all "left" graph branches
        foreach ($this->dependencies as $name => $relation) {
            if (!$relation->isCascade() || $state->visited($name)) {
                continue;
            }

            $origRelated[$name] = $state->getRelation($name);
            $this->queueRelation($sequence, $entity, $data, $state, $command, $relation, $name);
        }

        // queue target entity
        $sequence->addPrimary($command);

        // queue all "right" graph branches
        foreach ($this->relations as $name => $relation) {
            if (!$relation->isCascade() || $state->visited($name)) {
                continue;
            }

            $origRelated[$name] = $state->getRelation($name);
            $this->queueRelation($sequence, $entity, $data, $state, $command, $relation, $name);
        }

        if (count($sequence) === 1) {
            return $sequence->getPrimary();
        }

        return $sequence;
    }

    /**
     * Queue relation and return related object.
     *
     * @param Sequence          $sequence
     * @param object            $entity
     * @param array             $data
     * @param Point             $state
     * @param CarrierInterface  $command
     * @param RelationInterface $relation
     * @param string            $name
     */
    private function queueRelation(
        Sequence $sequence,
        $entity,
        array $data,
        Point $state,
        CarrierInterface $command,
        RelationInterface $relation,
        string $name
    ) {
        $state->markVisited($name);

        // get the current relation value
        $related = $relation->extract($data[$name] ?? null);

        // no changes in promised relation
        if ($related instanceof PromiseInterface && $related === $state->getRelation($name)) {
            return;
        }

        $relStore = $relation->queueRelation($command, $entity, $state, $related, $state->getRelation($name));

        if ($relStore instanceof Sequence && count($relStore) === 1) {
            // todo: improve
            $relStore = $relStore->getCommands()[0];
        }

        // queue needed changes
        $sequence->addCommand($relStore);

        // update current relation state
        $state->setRelation($name, $related);

        return;
    }
}