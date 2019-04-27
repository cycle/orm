<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Promise\ReferenceInterface;

/**
 * To create proxies, references, custom promises and etc. This class is similar to PromiseFactoryInterface
 * but it use ORM as scope so it can be nested to ORM as provider.
 */
interface PromiseFactoryInterface
{
    /**
     * @param ORMInterface $orm
     * @param string       $role
     * @param array        $scope
     * @return ReferenceInterface|null
     */
    public function promise(ORMInterface $orm, string $role, array $scope): ?ReferenceInterface;
}