<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\Collection\CollectionPromise;
use Cycle\ORM\Promise\PromiseMany;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasMany;
use Doctrine\Common\Collections\ArrayCollection;

class MorphedHasMany extends HasMany
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
            return [new ArrayCollection(), null];
        }

        $p = new PromiseMany(
            $this->orm,
            $this->target,
            [
                $this->outerKey => $innerKey,
                $this->morphKey => $node->getRole(),
            ],
            $this->schema[Relation::WHERE] ?? []
        );

        return [new CollectionPromise($p), $p];
    }

    /**
     * Persist related object.
     *
     * @param Node   $node
     * @param object $related
     * @return ContextCarrierInterface
     */
    protected function queueStore(Node $node, $related): ContextCarrierInterface
    {
        $rStore = parent::queueStore($node, $related);

        $rNode = $this->getNode($related);
        if ($this->fetchKey($rNode, $this->morphKey) != $node->getRole()) {
            $rStore->register($this->morphKey, $node->getRole(), true);
            $rNode->register($this->morphKey, $node->getRole(), true);
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
