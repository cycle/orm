<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Entity;

use Spiral\ORM\Exception\MapperException;
use Spiral\ORM\MapperInterface;
use Spiral\ORM\PromiseInterface;
use Zend\Stdlib\ResponseInterface;

/**
 * Provides mapper ability to initiate proxied version of it's entities.
 */
interface ProxyFactoryInterface extends MapperInterface
{
    /**
     * Create entity proxy.
     *
     * @param ResponseInterface $repository
     * @param array             $scope
     * @return PromiseInterface|null
     *
     * @throws MapperException
     */
    public function makeProxy(ResponseInterface $repository, array $scope): ?PromiseInterface;
}