<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\ReferenceInterface;

trait NodeTrait
{
    /**
     * Get Node for the given entity. Null if entity does not exists.
     */
    protected function getNode(?object $entity): ?Node
    {
        if ($entity === null) {
            return null;
        }

        if ($entity instanceof ReferenceInterface) {
            if ($entity->hasValue()) {
                $entity = $entity->getValue();
            } else {
                return new Node(Node::PROMISED, $entity->getScope(), $entity->getRole());
            }
        }

        /** @var Node|null $node */
        $node = $this->orm->getHeap()->get($entity);

        if ($node === null) {
            // possibly rely on relation target role, it will allow context switch
            $node = new Node(Node::NEW, [], $this->orm->getMapper($entity)->getRole());
            $this->orm->getHeap()->attach($entity, $node);
        }
        if (!$node->hasState()) {
            $node->setData($this->orm->getMapper($entity)->fetchFields($entity));
        }

        return $node;
    }
}
