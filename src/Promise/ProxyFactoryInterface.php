<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Promise;

use Cycle\ORM\Exception\MapperException;

/**
 * Provides mapper ability to initiate proxy version of it's entities.
 */
interface ProxyFactoryInterface
{
    /**
     * Create entity proxy.
     *
     * @param array $scope
     * @return PromiseInterface|null
     *
     * @throws MapperException
     */
    public function makeProxy(array $scope): ?PromiseInterface;
}