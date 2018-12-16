<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
            return [null, null];
        }

        $query = [
            $this->outerKey => $innerKey,
            $this->morphKey => $parentNode->getRole()
        ];

        $p = new PromiseOne($this->orm, $this->target, $query);

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