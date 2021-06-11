<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Heap\Node;

trait PromiseOneTrait
{
    public function initPromise(Node $parentNode): array
    {
        $scope = [];
        $parentNodeData = $parentNode->getData();
        foreach ($this->innerKeys as $i => $key) {
            if (empty($parentNodeData[$key])) {
                return [null, null];
            }
            $scope[$this->outerKeys[$i]] = $parentNodeData[$key];
        }

        $r = $this->orm->promise($this->target, $scope);

        return [$r, $r];
    }
}
