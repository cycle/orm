<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Relation\Traits\PromiseOneTrait;

/**
 * Provides ability to link to the parent object.
 * Will claim branch up to the parent object and it's relations. To disable
 * branch walk-through use RefersTo relation.
 */
class BelongsTo extends AbstractRelation implements DependencyInterface
{
    use PromiseOneTrait;

    public function queue(CC $store, $entity, Node $node, $related, $original): CommandInterface
    {
        if ($related === null) {
            if (!$this->isNullable()) {
                throw new NullException("Relation {$this} can not be null");
            }

            if ($original !== null) {
                // reset keys
                foreach ($this->innerKeys as $innerKey) {
                    $store->register($this->columnName($node, $innerKey), null, true);
                }
            }

            // nothing to do
            return new Nil();
        }

        $rStore = $this->orm->queueStore($related);
        $rNode = $this->getNode($related);
        $this->assertValid($rNode);

        $this->forwardContext(
            $rNode,
            $this->outerKeys,
            $store,
            $node,
            $this->innerKeys
        );

        return $rStore;
    }
}
