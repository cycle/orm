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
use Cycle\ORM\Command\Database\Insert;
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
    public function queue(CC $store, object $entity, Node $node, ?object $related, $original): CommandInterface
    {
        // refers-to relation is always nullable (as opposite to belongs-to)
        if ($related === null) {
            if ($original !== null) {
                foreach ($this->innerKeys as $innerKey) {
                    $store->register($this->columnName($node, $innerKey), null, true);
                }
            }

            return new Nil();
        }

        $rNode = $this->getNode($related);
        $this->assertValid($rNode);

        $returnNil = true;
        // related object exists, we can update key immediately
        foreach ($this->outerKeys as $i => $outerKey) {
            $outerValue = $this->fetchKey($rNode, $outerKey);
            $innerKey = $this->innerKeys[$i];

            if ($outerValue === null) {
                $returnNil = false;
                continue;
            }
            if ($outerValue != $this->fetchKey($node, $innerKey)) {
                $store->register($this->columnName($node, $innerKey), $outerValue, true);
            }
        }
        if ($returnNil) {
            $this->forwardContext($rNode, $this->outerKeys, $store, $node, $this->innerKeys);
            return new Nil();
        }

        // update parent entity once related instance is able to provide us context key
        $update = new Update(
            $this->getSource($node->getRole())->getDatabase(),
            $this->getSource($node->getRole())->getTable()
        );

        $this->forwardContext($rNode, $this->outerKeys, $update, $node, $this->innerKeys);
        if ($store instanceof Insert) {
            $update->waitCommand($store);
        }

        // fastest way to identify the entity
        $pk = (array)$this->orm->getSchema()->define($node->getRole(), Schema::PRIMARY_KEY);

        // set where condition for update query
        $this->forwardScope($node, $pk, $update, $pk);

        return $update;
    }
}
