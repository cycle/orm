<?php

declare(strict_types=1);

namespace Cycle\ORM\Promise;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\PromiseFactoryInterface;

/**
 * Returns PromiseOne for all entities.
 */
final class PromiseFactory implements PromiseFactoryInterface
{
    public function promise(ORMInterface $orm, string $role, array $scope): PromiseInterface
    {
        // doing nothing by default
        return new PromiseOne($orm, $role, $scope);
    }
}
