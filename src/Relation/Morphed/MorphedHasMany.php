<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\Collection\CollectionPromise;
use Cycle\ORM\Promise\PromiseMany;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasMany;
use Cycle\ORM\Transaction\Tuple;
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

    protected function applyChanges(Tuple $parentTuple, Tuple $rTuple): void
    {
        parent::applyChanges($parentTuple, $rTuple);

        $rNode = $rTuple->node;
        $node = $parentTuple->node;
        if ($this->fetchKey($rNode, $this->morphKey) !== $node->getRole()) {
            $rNode->register($this->morphKey, $node->getRole(), true);
        }
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
