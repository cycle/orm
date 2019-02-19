<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;


use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\ProxyFactoryInterface;

class UserMapperWithProxy extends Mapper implements ProxyFactoryInterface
{
    public function makeProxy(array $scope): ?PromiseInterface
    {
        return new UserProxy($this->orm, $this->role, $scope);
    }
}