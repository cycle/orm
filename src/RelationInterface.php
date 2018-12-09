<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface as CC;
use Spiral\ORM\Exception\RelationException;

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
     * Return the entity role relation points to.
     *
     * @return string
     */
    public function getRole(): string;

    /**
     * Must return true to trigger queue.
     *
     * @return bool
     */
    public function isCascade(): bool;

    /**
     * Init related entity value(s). Returns tupe [value, value to store as relation context].
     *
     * @param array $data
     * @return array
     *
     * @throws RelationException
     */
    public function init(array $data): array;

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
     * @param Node $point
     * @return array
     *
     * @throws RelationException
     */
    public function initPromise(Node $point): array;

    /**
     * Create branch of operations required to store the relation.
     *
     * @param CC     $parentStore
     * @param object $parentEntity
     * @param Node   $parentNode
     * @param object $related
     * @param object $original
     * @return CommandInterface
     *
     * @throws RelationException
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface;
}