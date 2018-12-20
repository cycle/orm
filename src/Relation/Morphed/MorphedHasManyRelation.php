<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Relation\Morphed;

use Doctrine\Common\Collections\ArrayCollection;
use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Promise\Collection\CollectionPromise;
use Spiral\Cycle\Promise\PromiseMany;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Relation\HasManyRelation;

class MorphedHasManyRelation extends HasManyRelation
{
    /** @var string */
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
        $this->morphKey = $schema[Relation::MORPH_KEY];
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
            $this->orm,
            $this->target,
            [
                $this->outerKey => $innerKey,
                $this->morphKey => $parentNode->getRole(),
            ],
            $this->schema[Relation::WHERE] ?? []
        );
        $p->setConstrain($this->getConstrain());

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