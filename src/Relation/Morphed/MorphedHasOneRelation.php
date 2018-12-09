<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Morphed;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface;
use Spiral\ORM\Node;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\HasOneRelation;
use Spiral\ORM\Util\Promise;

/**
 * Inverted version of belongs to morphed.
 */
class MorphedHasOneRelation extends HasOneRelation
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
            return [null, null];
        }

        $p = new Promise\PromiseOne(
            $this->orm,
            $this->targetRole,
            [
                $this->outerKey => $innerKey,
                $this->morphKey => $point->getRole()
            ],
            $point->getRole()
        );

        return [$p, $p];
    }

    /**
     * @inheritdoc
     */
    public function queue(
        ContextCarrierInterface $parentStore,
        $parentEntity,
        Node $parentNode,
        $related,
        $original
    ): CommandInterface {
        $store = parent::queue($parentStore, $parentEntity, $parentNode, $related, $original);

        if ($store instanceof ContextCarrierInterface && !is_null($related)) {
            $relState = $this->getNode($related);
            if ($this->fetchKey($relState, $this->morphKey) != $parentNode->getRole()) {
                $store->register($this->morphKey, $parentNode->getRole(), true);
                $relState->setData([$this->morphKey => $parentNode->getRole()]);
            }
        }

        return $store;
    }
}