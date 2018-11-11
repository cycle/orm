<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

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
                $data[$name] = $relation->init($data[$name]);
                $state->setRelation($name, $data[$name]);
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
        $chain->addTargetCommand($command);


        foreach ($this->relations as $relation) {
            $chain->addCommand($relation->queueChange($entity, $state, $command));
        }

        return $chain;
    }

    public function getRelation(string $relation)
    {
        // can be promise ?
    }
}