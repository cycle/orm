<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;


use Spiral\ORM\Collection\PivotedCollection;
use Spiral\ORM\Collection\RelationContext;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCommandInterface;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\Exception\RelationException;
use Spiral\ORM\Iterator;
use Spiral\ORM\State;
use Spiral\ORM\Util\ContextStorage;

class ManyToManyRelation extends AbstractRelation
{
    public const COLLECTION = true;

    public function initArray(array $data)
    {
        $iterator = new Iterator($this->orm, $this->class, $data);

        $entities = [];
        $pivotData = new \SplObjectStorage();
        foreach ($iterator as $pivot => $entity) {
            $entities[] = $entity;

            // todo: pivot model? (WHY NOT)

            $pivotData->offsetSet($entity, $pivot);
        }

        return new ContextStorage($entities, $pivotData);
    }

    public function wrapCollection($data)
    {
        if (!$data instanceof ContextStorage) {
            throw new RelationException("ManyToMany relation expect PivotData");
        }

        return new PivotedCollection($data->getData(), new RelationContext($data->getContext()));
    }

    public function queueChange(
        $parent,
        State $state,
        $related,
        ContextCommandInterface $command
    ): CommandInterface {

        // store or not to store?
        // cascade can only update

        return new NullCommand();
    }
}