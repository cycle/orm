<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Promise;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\PromiseFactoryInterface;

/**
 * Returns PromiseOne for all entities.
 */
final class PromiseFactory implements PromiseFactoryInterface
{
    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $scope
     * @return PromiseInterface
     */
    public function promise(ORMInterface $orm, string $target, array $scope): PromiseInterface
    {
        // doing nothing by default
        return new PromiseOne($orm, $target, $scope);
    }
}
