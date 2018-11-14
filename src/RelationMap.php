<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Spiral\ORM\Command\ChainCommand;
use Spiral\ORM\Command\CommandPromiseInterface;

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

    public function queueRelations(
        $entity,
        CommandPromiseInterface $command
    ): CommandPromiseInterface {
        // todo: what if entity new?
        $state = $this->orm->getHeap()->get($entity);

        $chain = new ChainCommand();

        foreach ($this->relations as $relation) {
            if ($relation->isCascade() && $relation->isLeading()) {
                $chain->addCommand($relation->queueChange($entity, $state, $command));
            }
        }

        $chain->addTargetCommand($command);

        foreach ($this->relations as $relation) {
            if ($relation->isCascade() && !$relation->isLeading()) {
                $chain->addCommand($relation->queueChange($entity, $state, $command));
            }
        }

        return $chain;
    }
}