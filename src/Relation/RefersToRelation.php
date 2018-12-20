<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Relation;

use Spiral\Cycle\Command\Branch\Nil;
use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface as CC;
use Spiral\Cycle\Command\Database\Update;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Relation\Traits\PromiseOneTrait;
use Spiral\Cycle\Schema;

/**
 * Variation of belongs-to relation which provides the ability to be self. Relation can be used
 * to create cyclic references. Relation does not trigger store operation of referenced object!
 */
class RefersToRelation extends AbstractRelation implements DependencyInterface
{
    use PromiseOneTrait;

    /**
     * @inheritdoc
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface
    {
        // refers-to relation is always nullable (as opposite to belongs-to)
        if (is_null($related)) {
            if (!is_null($original)) {
                $parentStore->register($this->innerKey, null, true);
            }

            return new Nil();
        }

        $relNode = $this->getNode($related);

        // related object exists, we can update key immediately
        if (!empty($outerKey = $this->fetchKey($relNode, $this->outerKey))) {
            if ($outerKey != $this->fetchKey($parentNode, $this->innerKey)) {
                $parentStore->register($this->innerKey, $outerKey, true);
            }

            return new Nil();
        }

        // update parent entity once related instance is able to provide us context key
        $update = new Update(
            $this->getSource($parentNode->getRole())->getDatabase(),
            $this->getSource($parentNode->getRole())->getTable()
        );

        // fastest way to identify the entity
        $pk = $this->orm->getSchema()->define($parentNode->getRole(), Schema::PRIMARY_KEY);

        $this->forwardContext(
            $relNode,
            $this->outerKey,
            $update,
            $parentNode,
            $this->innerKey
        );

        // set where condition for update query
        $this->forwardScope(
            $parentNode,
            $pk,
            $update,
            $pk
        );

        return $update;
    }
}
