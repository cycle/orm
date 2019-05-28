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

        $e = $this->orm->getHeap()->find($this->target, $this->outerKey, $innerKey);
        if ($e !== null || !$this->isPromised()) {
            return [$e, $e];
        }

        $r = $this->orm->promise($this->target, [$this->outerKey => $innerKey]);

        return [$r, $r];
    }
}