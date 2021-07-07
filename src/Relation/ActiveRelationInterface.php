<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\Reference;
use Cycle\ORM\Promise\ReferenceInterface;

/**
 * Manages single branch type between parent entity and other objects.
 */
interface ActiveRelationInterface extends RelationInterface
{
    /**
     * Init related entity value(s). Returns tuple [value, value to store as relation context]. If data null
     * relation must initiate empty relation state (when lazy loading is off).
     *
     * @param Node $node Parent node.
     * @return mixed
     * @throws RelationException
     */
    public function init(Node $node, array $data);

    public function initReference(Node $node): ReferenceInterface;

    /**
     * @return mixed
     */
    public function resolve(ReferenceInterface $reference, bool $load);

    /**
     * @param null|object|iterable $data
     *
     * @return null|object|iterable
     */
    public function collect($data);
}
