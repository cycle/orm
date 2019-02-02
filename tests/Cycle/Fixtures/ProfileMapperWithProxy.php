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
use Spiral\Cycle\Promise\PromiseOne;
use Spiral\Cycle\Promise\ProxyFactoryInterface;

class ProfileMapperWithProxy extends Mapper implements ProxyFactoryInterface
{
    public function makeProxy(array $scope): ?PromiseInterface
    {
        $p = new PromiseOne($this->orm, $this->role, $scope);
        $p->setConstrain($this->orm->getSource($this->role)->getConstrain());

        return new ProfileProxy($p);
    }
}