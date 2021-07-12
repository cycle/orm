<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\DeferredReference;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Reference\ReferenceInterface;

trait PromiseOneTrait
{
    public function initReference(Node $node): ReferenceInterface
    {
        $scope = $this->getReferenceScope($node);
        if ($scope === null) {
            $result = new Reference($this->target, []);
            $result->setValue(null);
            return $result;
        }
        if ($scope === [] && $this->isNullable()) {
            $result = new DeferredReference($this->target, []);
            $result->setValue(null);
            return $result;
        }
        return $scope === [] ? new DeferredReference($this->target, []) :  new Reference($this->target, $scope);
    }

    public function collect($source): ?object
    {
        return $source;
    }

    protected function getReferenceScope(Node $node): ?array
    {
        $scope = [];
        $nodeData = $node->getData();
        foreach ($this->innerKeys as $i => $key) {
            if (!array_key_exists($key, $nodeData)) {
                return [];
            }
            if ($nodeData[$key] === null) {
                return null;
            }
            $scope[$this->outerKeys[$i]] = $nodeData[$key];
        }
        return $scope;
    }
}
