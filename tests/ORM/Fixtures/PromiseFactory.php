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
use Cycle\ORM\PromiseFactoryInterface;

class PromiseFactory implements PromiseFactoryInterface
{
    public function promise(ORMInterface $orm, string $role, array $scope): PromiseInterface
    {
        switch ($role) {
            case 'user':
                return new UserPromise($orm, 'user', $scope);
            case 'profile':
                return new ProfilePromise($orm, 'profile', $scope);
        }

        return new PromiseOne($orm, $role, $scope);
    }
}
