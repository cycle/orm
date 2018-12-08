<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Morphed;


use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Point;
use Spiral\ORM\PromiseInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\BelongsToRelation;
use Spiral\ORM\Util\Promise;

class BelongsToMorphedRelation extends BelongsToRelation
{
    /** @var mixed|null */
    private $morphKey;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     * @param string       $relation
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        parent::__construct($orm, $class, $relation, $schema);
        $this->morphKey = $this->define(Relation::MORPH_KEY);
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Point $point): array
    {
        if (empty($innerKey = $this->fetchKey($point, $this->innerKey))) {
            return [null, null];
        }

        // parent class (todo: i don't need it!!!!!!!! use aliases directly)
        // todo: yeeeep, need aliases directly

        $parentClass = $this->orm->getSchema()->getClass($this->fetchKey($point, $this->morphKey));

        $scope = [$this->outerKey => $innerKey];

        if (!empty($e = $this->orm->fetchOne($parentClass, $scope, false))) {
            return [$e, $e];
        }


        //        // todo: i don't like carrying alias in a context (!!!!)
        //        // this is not right (!!)
        $pr = new Promise(
            [
                $this->outerKey => $innerKey,
                $this->morphKey => $this->fetchKey($point, $this->morphKey)
            ]
            , function ($context) use ($innerKey) {

            $parentClass = $this->orm->getSchema()->getClass($context[$this->morphKey]);

            if ($this->orm->getHeap()->hasPath("{$parentClass}:$innerKey")) {
                // todo: has it!
                $i = $this->orm->getHeap()->getPath("{$parentClass}:$innerKey");
                return $i;
            }

            // todo: optimize
            return $this->orm->getMapper($parentClass)->getRepository()->findOne([
                $this->outerKey => $context[$this->outerKey]
            ]);
        });

        return [$pr, $pr];
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
        $store = parent::queueRelation($parentCommand, $parentEntity, $parentState, $related, $original);

        // todo: use forward as well

        if (is_null($related)) {
            if ($this->fetchKey($parentState, $this->morphKey) !== null) {
                $parentCommand->push($this->morphKey, null, true);
                $parentState->setData([$this->morphKey => null]);
            }
        } else {
            $relState = $this->getPoint($related);
            if ($this->fetchKey($parentState, $this->morphKey) != $relState->getRole()) {
                $parentCommand->push($this->morphKey, $relState->getRole(), true);
                $parentState->setData([$this->morphKey => $relState->getRole()]);
            }
        }

        return $store;
    }

    protected function getPoint($entity, int $claim = 0): ?Point
    {
        if ($entity instanceof PromiseInterface) {
            $scope = $entity->__scope();

            return new Point(
                Point::PROMISED,
                [$this->outerKey => $scope[$this->outerKey]],
                $scope[$this->morphKey]
            );
        }

        return parent::getPoint($entity, $claim);
    }
}