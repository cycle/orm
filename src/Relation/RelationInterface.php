<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;

/**
 * Manages single branch type between parent entity and other objects.
 */
interface RelationInterface
{
    /**
     * Relation name.
     */
    public function getName(): string;

    /**
     * Target entity role.
     */
    public function getTarget(): string;

    /**
     * Must return true to trigger queue.
     */
    public function isCascade(): bool;

    /**
     * Init related entity value(s). Returns tuple [value, value to store as relation context]. If data null
     * relation must initiate empty relation state (when lazy loading is off).
     *
     * @param Node $node Parent node.
     *
     * @throws RelationException
     */
    public function init(Node $node, array $data): array;

    /**
     * Extract the related values from the entity field value.
     *
     * @param mixed $value
     * @return mixed
     *
     * @throws RelationException
     */
    public function extract($value);

    /**
     * Returns tuple of [promise to insert into entity, promise to store as relation context].
     *
     * @throws RelationException
     */
    public function initPromise(Node $node): array;

    /**
     * Create branch of operations required to store the relation.
     *

     * @param object|array|null $related
     * @param object|array|null $original
     *
     * @throws RelationException
     */
    public function queue(CC $store, object $entity, Node $node, $related, $original): CommandInterface;
}
