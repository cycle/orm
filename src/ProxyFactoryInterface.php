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
use Cycle\ORM\Promise\ReferenceInterface;

/**
 * To create proxies, references, custom promises and etc. This class is similar to PromiseFactoryInterface
 * but it use ORM as scope so it can be nested to ORM as provider.
 */
interface ProxyFactoryInterface
{
    /**
     * Create proxy using object reference. Implementation must not resolve reference if it's provided in a form
     * of PromiseInterface!
     *
     * @param ORMInterface       $orm
     * @param ReferenceInterface $reference
     * @return PromiseInterface
     */
    public function proxy(ORMInterface $orm, ReferenceInterface $reference): PromiseInterface;
}