<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\Reference;
use Cycle\ORM\Promise\ReferenceInterface;

trait PromiseOneTrait
{
    // public function initPromise(Node $parentNode): array
    // {
    //     $scope = [];
    //     $parentNodeData = $parentNode->getData();
    //     foreach ($this->innerKeys as $i => $key) {
    //         if (empty($parentNodeData[$key])) {
    //             return [null, null];
    //         }
    //         $scope[$this->outerKeys[$i]] = $parentNodeData[$key];
    //     }
    //
    //     /** @var ORMInterface $orm */
    //     $orm = $this->orm;
    //     $r = $orm->promise($this->target, $scope);
    //
    //     return [$r, $r];
    // }

    public function initReference(Node $node): ReferenceInterface
    {
        $scope = [];
        $nodeData = $node->getData();
        foreach ($this->innerKeys as $i => $key) {
            if (!array_key_exists($key, $nodeData)) {
                return new \Cycle\ORM\Promise\DeferredReference($node->getRole(), []);
            }
            if ($nodeData[$key] === null) {
                $result = new \Cycle\ORM\Promise\Reference($node->getRole(), [$this->outerKeys[$i] => null]);
                $result->setValue(null);
            }
            $scope[$this->outerKeys[$i]] = $nodeData[$key];
        }

        return new Reference($this->target, $scope);
    }

    // public function initDeferred(Node $node)
    // {
    //     $scope = [];
    //     $parentNodeData = $node->getData();
    //     foreach ($this->innerKeys as $i => $key) {
    //         if (!isset($parentNodeData[$key])) {
    //             return new DeferredRelation($this, $node);
    //         }
    //         $scope[$this->outerKeys[$i]] = $parentNodeData[$key];
    //     }
    //     /*
    //      * todo Search in the Heap can be deferred until the relation value is needed
    //      */
    //     /** @var HeapInterface $heap */
    //     $heap = $this->orm->getHeap();
    //
    //     return $heap->find($this->target, $scope) ?? new DeferredPromise(new PromiseOne($this->orm, $this->target, $scope));
    // }
}
