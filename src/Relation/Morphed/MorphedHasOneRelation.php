<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Relation\Morphed;

use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface as CC;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Mapper\ProxyFactoryInterface;
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Promise\PromiseOne;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Relation\HasOneRelation;

/**
 * Inverted version of belongs to morphed.
 */
class MorphedHasOneRelation extends HasOneRelation
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
        if (is_null($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [null, null];
        }

        $scope = [
            $this->outerKey => $innerKey,
            $this->morphKey => $parentNode->getRole()
        ];

        if (!is_null($e = $this->orm->getHeap()->find($this->target, $scope))) {
            return [$e, $e];
        }

        $p = new PromiseOne($this->orm, $this->target, $scope);
        $p->setConstrain($this->getConstrain());

        $m = $this->getSource();
        if ($m instanceof ProxyFactoryInterface) {
            $p = $m->makeProxy($p);
        }

        return [$p, $p];
    }

    /**
     * @inheritdoc
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface
    {
        $relStore = parent::queue($parentStore, $parentEntity, $parentNode, $related, $original);

        if ($relStore instanceof CC && !is_null($related)) {
            $relNode = $this->getNode($related);

            if ($this->fetchKey($relNode, $this->morphKey) != $parentNode->getRole()) {
                $relStore->register($this->morphKey, $parentNode->getRole(), true);
                $relNode->register($this->morphKey, $parentNode->getRole(), true);
            }
        }

        return $relStore;
    }
}