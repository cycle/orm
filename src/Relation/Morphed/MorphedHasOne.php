<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasOne;

/**
 * Inverted version of belongs to morphed.
 */
class MorphedHasOne extends HasOne
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
    public function initPromise(Node $node): array
    {
        $innerKey = $this->fetchKey($node, $this->innerKey);
        if ($innerKey === null) {
            return [null, null];
        }

        $scope = [
            $this->outerKey => $innerKey,
            $this->morphKey => $node->getRole()
        ];

        $r = $this->orm->promise($this->target, $scope);
        return [$r, $r];
    }

    /**
     * @inheritdoc
     */
    public function queue(CC $store, $entity, Node $node, $related, $original): CommandInterface
    {
        $rStore = parent::queue($store, $entity, $node, $related, $original);

        if ($rStore instanceof CC && $related !== null) {
            $rNode = $this->getNode($related);

            if ($this->fetchKey($rNode, $this->morphKey) != $node->getRole()) {
                $rStore->register($this->morphKey, $node->getRole(), true);
                $rNode->register($this->morphKey, $node->getRole(), true);
            }
        }

        return $rStore;
    }

    /**
     * Assert that given entity is allowed for the relation.
     *
     * @param Node $related
     *
     * @throws RelationException
     */
    protected function assertValid(Node $related): void
    {
        // no need to validate morphed relation yet
    }
}
