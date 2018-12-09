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

        $p = new Promise\PromiseManyX($this->orm, $this->targetRole, [
            $this->outerKey => $innerKey,
            $this->morphKey => $point->getRole()
        ]);

        return [new CollectionPromise($p), $p];
    }

    /**
     * Persist related object.
     *
     * @param Node   $parentNode
     * @param object $related
     * @return ContextCarrierInterface
     */
    protected function queueStore(Node $parentNode, $related): ContextCarrierInterface
    {
        $store = parent::queueStore($parentNode, $related);

        $relState = $this->getNode($related);
        if ($this->fetchKey($relState, $this->morphKey) != $parentNode->getRole()) {
            // polish it
            $store->register($this->morphKey, $parentNode->getRole(), true);

            // todo: update store only?
            $relState->setData([$this->morphKey => $parentNode->getRole()]);
        }

        return $store;
    }
}