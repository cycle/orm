<?php

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
    private string $morphKey;

    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->morphKey = $schema[Relation::MORPH_KEY];
    }

    public function initPromise(Node $node): array
    {
        $innerValues = [];
        foreach ($this->innerKeys as $i => $innerKey) {
            $innerValue = $this->fetchKey($node, $innerKey);
            if ($innerValue === null) {
                return [new ArrayCollection(), null];
            }
            $innerValues[] = $innerValue;
        }

        $p = new PromiseMany(
            $this->orm,
            $this->target,
            array_combine($this->outerKeys, $innerValues) + [$this->morphKey => $node->getRole()],
            $this->schema[Relation::WHERE] ?? []
        );

        return [new CollectionPromise($p), $p];
    }

    /**
     * Persist related object.
     */
    protected function queueStore(Node $node, object $related): ContextCarrierInterface
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
     * @throws RelationException
     */
    protected function assertValid(Node $related): void
    {
        // no need to validate morphed relation yet
    }
}
