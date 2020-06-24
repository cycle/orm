<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\ReferenceInterface;

trait NodeTrait
{
    /**
     * Get Node for the given entity. Null if entity does not exists. Automatically
     * register entity claims.
     *
     * @param object $entity
     * @param int    $claim
     * @return Node|null
     */
    protected function getNode($entity, int $claim = 0): ?Node
    {
        if ($entity === null) {
            return null;
        }

        if ($entity instanceof PromiseInterface && $entity->__loaded()) {
            $entity = $entity->__resolve();
        }

        if ($entity instanceof ReferenceInterface) {
            return new Node(Node::PROMISED, $entity->__scope(), $entity->__role());
        }

        $node = $this->orm->getHeap()->get($entity);

        if ($node === null) {
            // possibly rely on relation target role, it will allow context switch
            $node = new Node(Node::NEW, [], $this->orm->getMapper($entity)->getRole());
            $this->orm->getHeap()->attach($entity, $node);
        }

        if ($claim === 1) {
            $node->getState()->addClaim();
        }

        if ($claim === -1) {
            $node->getState()->decClaim();
        }

        return $node;
    }
}
