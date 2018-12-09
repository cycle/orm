<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Morphed;

use Doctrine\Common\Collections\ArrayCollection;
use Spiral\ORM\Command\ContextCarrierInterface;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Node;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\HasManyRelation;
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

    /**
     * @inheritdoc
     */
    public function initPromise(Node $point): array
    {
        if (empty($innerKey = $this->fetchKey($point, $this->innerKey))) {
            return [new ArrayCollection(), null];
        }

        $p = new Promise\PromiseMany($this->orm, $this->class, [
            $this->outerKey => $innerKey,
            $this->morphKey => $point->getRole()
        ]);

        return [new CollectionPromise($p), $p];
    }

    /**
     * Persist related object.
     *
     * @param Node   $parent
     * @param object $related
     * @return ContextCarrierInterface
     */
    protected function queueStore(Node $parent, $related): ContextCarrierInterface
    {
        $store = parent::queueStore($parent, $related);

        $relState = $this->getPoint($related);
        if ($this->fetchKey($relState, $this->morphKey) != $parent->getRole()) {
            // polish it
            $store->register($this->morphKey, $parent->getRole(), true);

            // todo: update store only?
            $relState->setData([$this->morphKey => $parent->getRole()]);
        }

        return $store;
    }
}