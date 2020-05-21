<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Promise\PromiseInterface;

/**
 * To create proxies, references, custom promises and etc. This class is similar to PromiseFactoryInterface
 * but it use ORM as scope so it can be nested to ORM as provider.
 */
interface PromiseFactoryInterface
{
    /**
     * Create proxy using object reference. Implementation must not resolve reference if it's provided in a form
     * of PromiseInterface!
     *
     * @param ORMInterface $orm
     * @param string       $role
     * @param array        $scope
     * @return PromiseInterface
     */
    public function promise(
        ORMInterface $orm,
        string $role,
        array $scope
    ): PromiseInterface;
}
