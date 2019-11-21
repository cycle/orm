<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Relation\Traits\PromiseOneTrait;
use Cycle\ORM\Schema;

/**
 * Variation of belongs-to relation which provides the ability to be self linked. Relation can be used
 * to create cyclic references. Relation does not trigger store operation of referenced object!
 */
class RefersTo extends AbstractRelation implements DependencyInterface
{
    use PromiseOneTrait;

    /**
     * @inheritdoc
     */
    public function queue(CC $store, $entity, Node $node, $related, $original): CommandInterface
    {
        // refers-to relation is always nullable (as opposite to belongs-to)
        if ($related === null) {
            if ($original !== null) {
                $store->register($this->innerKey, null, true);
            }

            return new Nil();
        }

        $rNode = $this->getNode($related);
        $this->assertValid($rNode);

        // related object exists, we can update key immediately
        $outerKey = $this->fetchKey($rNode, $this->outerKey);
        if ($outerKey !== null) {
            if ($outerKey != $this->fetchKey($node, $this->innerKey)) {
                $store->register($this->innerKey, $outerKey, true);
            }

            return new Nil();
        }

        // update parent entity once related instance is able to provide us context key
        $update = new Update(
            $this->getSource($node->getRole())->getDatabase(),
            $this->getSource($node->getRole())->getTable()
        );

        // fastest way to identify the entity
        $pk = $this->orm->getSchema()->define($node->getRole(), Schema::PRIMARY_KEY);

        $this->forwardContext(
            $rNode,
            $this->outerKey,
            $update,
            $node,
            $this->innerKey
        );

        // set where condition for update query
        $this->forwardScope(
            $node,
            $pk,
            $update,
            $pk
        );

        return $update;
    }
}
