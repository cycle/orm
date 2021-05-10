<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Heap\Node;

trait PromiseOneTrait
{
    public function initPromise(Node $parentNode): array
    {
        $scope = [];
        foreach ($this->innerKeys as $i => $key) {
            $innerValue = $this->fetchKey($parentNode, $key);
            if (empty($innerValue)) {
                return [null, null];
            }
            $scope[$this->outerKeys[$i]] = $innerValue;
        }

        $r = $this->orm->promise($this->target, $scope);

        return [$r, $r];
    }

    /**
     * Fetch key from the state.
     *
     * @return mixed|null
     */
    abstract protected function fetchKey(Node $node, string $key);
}
