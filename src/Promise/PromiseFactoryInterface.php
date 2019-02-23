<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Promise;

use Cycle\ORM\Exception\PromiseException;

/**
 * Creates promises to objects in a form of PromiseInterface, ReferenceInterface or proxy.
 */
interface PromiseFactoryInterface
{
    /**
     * Method can return target entity if such already presented in heap memory.
     *
     * @param string $role
     * @param array  $scope
     * @return ReferenceInterface|mixed|null
     *
     * @throws PromiseException
     */
    public function promise(string $role, array $scope);
}