<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Mapper;

use Spiral\ORM\Exception\MapperException;
use Spiral\ORM\PromiseInterface;

/**
 * Provides mapper ability to initiate proxied version of it's entities.
 */
interface PromiseFactoryInterface
{
    /**
     * Create entity proxy.
     *
     * @param array $scope
     * @return PromiseInterface|null
     *
     * @throws MapperException
     */
    public function initProxy(array $scope): ?PromiseInterface;
}