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

class ProxyFactory implements ProxyFactoryInterface
{
    public function proxy(ORMInterface $orm, ReferenceInterface $reference): PromiseInterface
    {
        $role = $reference->__role();
        $scope = $reference->__scope();

        switch ($role) {
            case 'user':
                return new UserProxy($orm, 'user', $scope);
            case 'profile':
                return new ProfileProxy($orm, 'profile', $scope);
        }

        return new PromiseOne($orm, $role, $scope);
    }
}