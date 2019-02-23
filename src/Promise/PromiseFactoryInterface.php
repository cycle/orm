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
     * @param string $role
     * @param array  $scope
     * @return ReferenceInterface|null
     *
     * @throws PromiseException
     */
    public function promise(string $role, array $scope): ?ReferenceInterface;
}