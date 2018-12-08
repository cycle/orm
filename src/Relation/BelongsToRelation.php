<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Control\Nil;
use Spiral\ORM\DependencyInterface;
use Spiral\ORM\Exception\Relation\NullException;
use Spiral\ORM\Point;
use Spiral\ORM\Util\Promise;

// todo: what is the difference with refers to?
class BelongsToRelation extends AbstractRelation implements DependencyInterface
{
    /**
     * @inheritdoc
     */
    public function initPromise(Point $point): array
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
        CarrierInterface $parentCommand,
        $parentEntity,
        Point $parentState,
        $related,
        $original
    ): CommandInterface {
        if (is_null($related)) {
            if ($this->isRequired()) {
                throw new NullException("Relation {$this} can not be null");
            }

            if (!is_null($original)) {
                // push?
                $parentCommand->push($this->innerKey, null, true);
            }

            return new Nil();
        }

        $relStore = $this->orm->queueStore($related);
        $relState = $this->getPoint($related, +1);

        $this->addDependency($relState, $this->outerKey, $parentCommand, $parentState, $this->innerKey);

        return $relStore;
    }
}