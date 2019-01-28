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

class ProfileMapperWithProxy extends Mapper implements ProxyFactoryInterface
{
    public function makeProxy(PromiseInterface $promise): ?PromiseInterface
    {
        return new ProfileProxy($promise);
    }
}