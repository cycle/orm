<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Morphed;

use Doctrine\Common\Collections\ArrayCollection;
use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\HasManyRelation;
use Spiral\ORM\Point;
use Spiral\ORM\Util\Collection\CollectionPromise;
use Spiral\ORM\Util\Promise;

class MorphedHasManyRelation extends HasManyRelation
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

    public function initPromise(Point $point): array
    {
        // todo: here we need paths (!)
        if (empty($innerKey = $this->fetchKey($point, $this->innerKey))) {
            return [new ArrayCollection(), null];
        }

        $pr = new Promise(
            [
                $this->outerKey => $innerKey,
                $this->morphKey => $point->getRole()
            ],
            function (array $scope) use ($innerKey) {
                return $this->orm->getMapper($this->class)->getRepository()->findAll($scope);
            }
        );

        return [new CollectionPromise($pr), $pr];
    }

    /**
     * Persist related object.
     *
     * @param Point  $parent
     * @param object $related
     * @return CarrierInterface
     */
    protected function queueStore(Point $parent, $related): CarrierInterface
    {
        $store = parent::queueStore($parent, $related);

        $relState = $this->getPoint($related);
        if ($this->fetchKey($relState, $this->morphKey) != $parent->getRole()) {
            // polish it
            $store->push($this->morphKey, $parent->getRole(), true);

            // todo: update store only?
            $relState->setData([$this->morphKey => $parent->getRole()]);
        }

        return $store;
    }
}