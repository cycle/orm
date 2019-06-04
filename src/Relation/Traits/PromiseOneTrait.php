<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\Reference;

trait PromiseOneTrait
{
    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [null, null];
        }

        /** @var ORMInterface $orm */
        $orm = $this->orm;

        $e = $orm->getHeap()->find($this->target, $this->outerKey, $innerKey);
        if ($e !== null || !$this->isPromised()) {
            return [$e, $e];
        }

        $r = new Reference($this->target, [$this->outerKey => $innerKey]);
        if ($orm->getProxyFactory() !== null) {
            $r = $orm->getProxyFactory()->proxy($this->orm, $r);
        }

        return [$r, $r];
    }
}