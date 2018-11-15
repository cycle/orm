<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Spiral\ORM\Command\ChainContextCommand;
use Spiral\ORM\Command\ContextCommandInterface;

final class RelationMap
{
    /**
     * @invisible
     * @var ORMInterface
     */
    private $orm;

    /** @var RelationInterface[] */
    private $relations = [];

    public function __construct(ORMInterface $orm, array $relations)
    {
        $this->orm = $orm;
        $this->relations = $relations;
    }

    public function init(State $state, array $data): array
    {
        // easy to read huh?
        foreach ($this->relations as $name => $relation) {
            if (array_key_exists($name, $data)) {
                if (!$relation->isCollection()) {
                    if (!is_object($data[$name])) {
                        $data[$name] = $relation->init($data[$name]);
                    }
                    $state->setRelation($name, $data[$name]);
                } else {
                    foreach ($data[$name] as &$item) {
                        if (!is_object($item)) {
                            $item = $relation->init($item);
                        }
                        unset($item);
                    }

                    $state->setRelation($name, $data[$name]);
                    $data[$name] = new ArrayCollection($data[$name]);
                }
            }
        }

        return $data;
    }

    public function queueRelations($entity, ContextCommandInterface $command): ContextCommandInterface
    {
        // todo: what if entity new?
        $state = $this->orm->getHeap()->get($entity);

        $chain = new ChainContextCommand();

        $data = $this->orm->getMapper($entity)->extract($entity);

        foreach ($this->relations as $name => $relation) {
            if ($relation->isCascade() && $relation->isLeading()) {
                $chain->addCommand($relation->queueChange(
                    $entity,
                    $state,
                    $data[$name] ?? null,
                    $command
                ));
            }
        }

        $chain->addTargetCommand($command);

        foreach ($this->relations as $name => $relation) {
            if ($relation->isCascade() && !$relation->isLeading()) {
                $chain->addCommand($relation->queueChange(
                    $entity,
                    $state,
                    $data[$name] ?? null,
                    $command
                ));
            }
        }

        return $chain;
    }
}