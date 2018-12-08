<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\CommandInterface;

interface RelationInterface
{
    public function isCascade(): bool;

    public function init($data): array;

    public function initPromise(Point $point): array;

    public function extract($value);

    public function queueRelation(
        CarrierInterface $parentCommand,
        $parentEntity,
        Point $parentState,
        $related,
        $original
    ): CommandInterface;
}