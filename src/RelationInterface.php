<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CarrierInterface;

interface RelationInterface
{
    public function isCascade(): bool;

    public function init($data): array;

    public function initPromise(Point $state, $data): array;

    public function extract($value);

    public function queueRelation(
        CarrierInterface $parentCommand,
        $entity,
        Point $state,
        $related,
        $original
    ): CommandInterface;
}