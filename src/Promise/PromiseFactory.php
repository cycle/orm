<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Promise;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\ProxyFactoryInterface;

/**
 * Returns PromiseOne for all entities.
 */
final class PromiseFactory implements ProxyFactoryInterface
{
    /**
     * @param ORMInterface       $orm
     * @param ReferenceInterface $reference
     * @return PromiseInterface
     */
    public function proxy(ORMInterface $orm, ReferenceInterface $reference): PromiseInterface
    {
        if ($reference instanceof PromiseInterface) {
            return $reference;
        }

        // doing nothing by default
        return new PromiseOne($orm, $reference->__role(), $reference->__scope());
    }
}