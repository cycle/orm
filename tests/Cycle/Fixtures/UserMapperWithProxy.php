<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests\Fixtures;


use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Promise\PromiseInterface;
use Spiral\Cycle\Promise\ProxyFactoryInterface;

class UserMapperWithProxy extends Mapper implements ProxyFactoryInterface
{
    public function makeProxy(array $scope): ?PromiseInterface
    {
        return new UserProxy($this->orm, $this->role, $scope);
    }
}