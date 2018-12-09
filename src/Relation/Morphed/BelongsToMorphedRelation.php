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
use Spiral\ORM\Mapper\ProxyFactoryInterface;
use Spiral\ORM\Node;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\BelongsToRelation;
use Spiral\ORM\Util\Promise;

class BelongsToMorphedRelation extends BelongsToRelation
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

        $parentRole = $this->fetchKey($parentNode, $this->morphKey);
        $scope = [$this->outerKey => $innerKey];

        if (!empty($e = $this->orm->get($parentRole, $scope, false))) {
            return [$e, $e];
        }

        $mapper = $this->getMapper($parentRole);
        if ($mapper instanceof ProxyFactoryInterface) {
            $p = $mapper->initProxy($scope);
        } else {
            $p = new Promise\PromiseOne($this->orm, $parentRole, $scope);
        }

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

        // todo: use forward as well

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

        return $store;
    }

    //    protected function getNode($entity, int $claim = 0): ?Node
    //    {
    //        if ($entity instanceof PromiseInterface) {
    //            $scope = $entity->__scope();
    //
    //            return new Node(
    //                Node::PROMISED,
    //                [$this->outerKey => $scope[$this->outerKey]],
    //                $scope[$this->morphKey]
    //            );
    //        }
    //
    //        return parent::getNode($entity, $claim);
    //    }
}