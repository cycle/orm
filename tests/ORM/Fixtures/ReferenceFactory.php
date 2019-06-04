<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\PromiseOne;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\ProxyFactoryInterface;

class ReferenceFactory implements ProxyFactoryInterface
{
    public function promise(ORMInterface $orm, string $role, array $scope): ?ReferenceInterface
    {
        switch ($role) {
            case 'user':
                return new UserID(current($scope));
        }

        return new PromiseOne($orm, $role, $scope);
    }

    public function proxyPromise(ORMInterface $orm, string $role, PromiseInterface $promise): PromiseInterface
    {
        return $promise;
    }
}