<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Target entity role.
     *
     * @return string
     */
    public function getTarget(): string;

    /**
     * Must return true to trigger queue.
     *
     * @return bool
     */
    public function isCascade(): bool;

    /**
     * Init related entity value(s). Returns tuple [value, value to store as relation context]. If data null
     * relation must initiate empty relation state (when lazy loading is off).
     *
     * @param Node       $node Parent node.
     * @param array|null $data
     *
     * @throws RelationException
     *
     * @return array
     */
    public function init(Node $node, array $data): array;

    /**
     * Extract the related values from the entity field value.
     *
     * @param mixed $value
     *
     * @throws RelationException
     *
     * @return mixed
     */
    public function extract($value);

    /**
     * Returns tuple of [promise to insert into entity, promise to store as relation context].
     *
     * @param Node $node
     *
     * @throws RelationException
     *
     * @return array
     */
    public function initPromise(Node $node): array;

    /**
     * Create branch of operations required to store the relation.
     *
     * @param CC     $store
     * @param object $entity
     * @param Node   $node
     * @param object $related
     * @param object $original
     *
     * @throws RelationException
     *
     * @return CommandInterface
     */
    public function queue(CC $store, $entity, Node $node, $related, $original): CommandInterface;
}
