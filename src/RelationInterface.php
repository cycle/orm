<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\ContextCarrierInterface;
use Spiral\ORM\Command\CommandInterface;

interface RelationInterface
{
    public function isCascade(): bool;

    public function init($data): array;

    public function initPromise(Node $point): array;

    public function extract($value);

    public function queueRelation(
        ContextCarrierInterface $parentCommand,
        $parentEntity,
        Node $parentState,
        $related,
        $original
    ): CommandInterface;
}