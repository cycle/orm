<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Promise;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\ProxyFactoryInterface;

/**
 * Returns PromiseOne for all entities.
 */
class PromiseFactory implements ProxyFactoryInterface
{
    /**
     * @param ORMInterface $orm
     * @param string       $role
     * @param array        $scope
     * @return ReferenceInterface|null
     */
    public function proxy(ORMInterface $orm, string $role, array $scope): ?ReferenceInterface
    {
        return new PromiseOne($orm, $role, $scope);
    }
}