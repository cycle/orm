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
use Cycle\ORM\ProxyFactoryInterface;

/**
 * Returns PromiseOne for all entities.
 */
final class ProxyFactory implements ProxyFactoryInterface
{
    /**
     * @param ORMInterface $orm
     * @param string       $role
     * @param array        $scope
     * @return ReferenceInterface|null
     */
    public function promise(ORMInterface $orm, string $role, array $scope): ?ReferenceInterface
    {
        return new PromiseOne($orm, $role, $scope);
    }

    /**
     * @param ORMInterface     $orm
     * @param string           $role
     * @param PromiseInterface $promise
     * @return PromiseInterface
     */
    public function proxyPromise(ORMInterface $orm, string $role, PromiseInterface $promise): PromiseInterface
    {
        // doing nothing by default
        return $promise;
    }
}