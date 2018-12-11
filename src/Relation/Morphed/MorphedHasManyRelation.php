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
use Spiral\ORM\Heap\Node;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Promise\PromiseMany;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\HasManyRelation;
use Spiral\ORM\Util\Collection\CollectionPromise;

class MorphedHasManyRelation extends HasManyRelation
{
    /** @var mixed|null */
    private $morphKey;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param string       $name
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->morphKey = $schema[Relation::MORPH_KEY] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [new ArrayCollection(), null];
        }

        $p = new PromiseMany(
            $this->getSource()->getSelector(),
            [
                $this->outerKey => $innerKey,
                $this->morphKey => $parentNode->getRole(),
            ],
            $this->schema[Relation::WHERE_SCOPE] ?? [],
            $this->schema[Relation::ORDER_BY] ?? []
        );

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
        $relStore = parent::queueStore($parentNode, $related);

        $relNode = $this->getNode($related);
        if ($this->fetchKey($relNode, $this->morphKey) != $parentNode->getRole()) {
            $relStore->register($this->morphKey, $parentNode->getRole(), true);
            $relNode->register($this->morphKey, $parentNode->getRole(), true);
        }

        return $relStore;
    }
}