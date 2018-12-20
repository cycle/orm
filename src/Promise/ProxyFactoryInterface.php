<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Mapper;

use Spiral\Cycle\Exception\MapperException;
use Spiral\Cycle\Promise\PromiseInterface;

/**
 * Provides mapper ability to initiate proxied version of it's entities.
 */
interface ProxyFactoryInterface
{
    /**
     * Create entity proxy.
     *
     * @param PromiseInterface $promise
     * @return PromiseInterface|null
     *
     * @throws MapperException
     */
    public function makeProxy(PromiseInterface $promise): ?PromiseInterface;
}