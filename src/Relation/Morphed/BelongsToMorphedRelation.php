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
use Spiral\Cycle\Relation\BelongsToRelation;
use Spiral\Cycle\Select\ConstrainInterface;
use Spiral\Cycle\Select\SourceInterface;

class BelongsToMorphedRelation extends BelongsToRelation
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

        /** @var string $target */
        $target = $this->fetchKey($parentNode, $this->morphKey);
        if (is_null($target)) {
            return [null, null];
        }

        $scope = [$this->outerKey => $innerKey];
        if (!is_null($e = $this->orm->getHeap()->find($target, $scope))) {
            return [$e, $e];
        }

        $p = new PromiseOne($this->orm, $target, $scope);
        $p->setConstrain($this->getTargetConstrain($target));

        $m = $this->getMapper($target);
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
        $wrappedStore = parent::queue($parentStore, $parentEntity, $parentNode, $related, $original);

        if (is_null($related)) {
            if ($this->fetchKey($parentNode, $this->morphKey) !== null) {
                $parentStore->register($this->morphKey, null, true);
                $parentNode->register($this->morphKey, null, true);
            }
        } else {
            $relState = $this->getNode($related);
            if ($this->fetchKey($parentNode, $this->morphKey) != $relState->getRole()) {
                $parentStore->register($this->morphKey, $relState->getRole(), true);
                $parentNode->register($this->morphKey, $relState->getRole(), true);
            }
        }

        return $wrappedStore;
    }

    /**
     * Get the scope name associated with the relation.
     *
     * @param string $target
     * @return null|ConstrainInterface
     */
    protected function getTargetConstrain(string $target): ?ConstrainInterface
    {
        $constrain = $this->schema[Relation::CONSTRAIN] ?? SourceInterface::DEFAULT_CONSTRAIN;
        if ($constrain instanceof ConstrainInterface) {
            return $constrain;
        }

        return $this->getSource($target)->getConstrain($constrain);
    }
}