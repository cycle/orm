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
use Spiral\ORM\DependencyInterface;
use Spiral\ORM\Exception\Relation\NullException;
use Spiral\ORM\Node;
use Spiral\ORM\Util\Promise;

// todo: what is the difference with refers to?
class BelongsToRelation extends AbstractRelation implements DependencyInterface
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
        if (is_null($related)) {
            if ($this->isRequired()) {
                throw new NullException("Relation {$this} can not be null");
            }

            if (!is_null($original)) {
                // push?
                $parentStore->register($this->innerKey, null, true);
            }

            return new Nil();
        }

        $relStore = $this->orm->queueStore($related);
        $relState = $this->getPoint($related, +1);

        $this->addDependency($relState, $this->outerKey, $parentStore, $parentNode, $this->innerKey);

        return $relStore;
    }
}