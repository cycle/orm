<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\ContextCarrierInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Branch\Nil;
use Spiral\ORM\Command\Database\Update;
use Spiral\ORM\DependencyInterface;
use Spiral\ORM\Node;
use Spiral\ORM\Schema;
use Spiral\ORM\Util\Promise;

/**
 * Variation of belongs-to relation which provides the ability to be self. Relation can be used
 * to create cyclic references. Relation does not trigger store operation of referenced object!
 *
 * @todo merge with belongs to (?)
 */
class RefersToRelation extends AbstractRelation implements DependencyInterface
{
    /**
     * @inheritdoc
     */
    public function initPromise(Node $point): array
    {
        if (empty($innerKey = $this->fetchKey($point, $this->innerKey))) {
            return [null, null];
        }

        $scope = [$this->outerKey => $innerKey];

        if (!empty($e = $this->orm->fetchOne($this->class, $scope, false))) {
            return [$e, $e];
        }

        $p = new Promise\PromiseOne($this->orm, $this->class, $scope);

        return [$p, $p];
    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextCarrierInterface $parentStore,
        $parentEntity,
        Node $parentNode,
        $related,
        $original
    ): CommandInterface {
        // refers-to relation is always nullable (as opposite to belongs-to)
        if (is_null($related)) {
            if (!is_null($original)) {
                $parentStore->register($this->innerKey, null, true);
            }

            return new Nil();
        }

        $relState = $this->getPoint($related, +1);

        // related object exists, we can update key immediately
        if (!empty($outerKey = $this->fetchKey($relState, $this->outerKey))) {
            if ($outerKey != $this->fetchKey($parentNode, $this->innerKey)) {
                $parentStore->register($this->innerKey, $outerKey, true);
            }

            return new Nil();
        }

        // this needs to be better

        // todo: use queue store? merge with belongs to?

        $relState = $this->getPoint($related);

        /*
         * REMEMBER THE CYCLES!!!!
         */


        //  if (!empty($relState->getCommand())) {
        //   $update = $relState->getCommand();

        // todo: how reliable is it? it's not
        //    if (!($update instanceof Insert)) {
        //     $this->forwardContext($relState, $this->outerKey, $update, $state, $this->innerKey);
        //       return new Nil();
        //  }
        //   }

        // why am i taking same command?
        $update = new Update(
            $this->orm->getDatabase($parentEntity),
            $this->orm->getSchema()->define(get_class($parentEntity), Schema::TABLE)
        );

        // todo: here we go, the problem is that i need UPDATE command to be automatically

        $primaryKey = $this->orm->getSchema()->define(get_class($parentEntity), Schema::PRIMARY_KEY);
        $this->forwardScope($parentNode, $primaryKey, $update, $primaryKey);
        $this->addDependency($this->getPoint($related), $this->outerKey, $update, $parentNode, $this->innerKey);

        return $update;
    }
}