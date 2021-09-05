<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\ReferenceInterface;

/**
 * Manages single branch type between parent entity and other objects.
 */
interface ActiveRelationInterface extends RelationInterface
{
    /**
     * Init related entity value(s).
     *
     * @param Node $node Parent node.
     */
    public function init(Node $node, array $data): object|iterable;

    public function initReference(Node $node): ReferenceInterface;

    /**
     * Resolve instance ReferenceInterface
     *
     * @param bool $load If is false then the result will only be searched in the heap only. Int his case the result of
     * a collection resolving always will be null because the Heap can't contain all items from collection.
     */
    public function resolve(ReferenceInterface $reference, bool $load): object|iterable|null;

    /**
     * @param iterable|object|null $data
     */
    public function collect($data): object|iterable|null;
}
