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

        $r = $this->orm->promise($this->target, [$this->outerKey => $innerKey]);

        return [$r, $r];
    }

    /**
     * Fetch key from the state.
     *
     * @param Node   $node
     * @param string $key
     * @return mixed|null
     */
    abstract protected function fetchKey(?Node $node, string $key);
}
