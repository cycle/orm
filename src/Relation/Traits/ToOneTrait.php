<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\EmptyReference;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Reference\ReferenceInterface;

/**
 * @internal
 */
trait ToOneTrait
{
    public function init(Node $node, array $data): object
    {
        $item = $this->orm->make($this->target, $data, Node::MANAGED);
        $node->setRelation($this->getName(), $item);
        return $item;
    }

    public function cast(?array $data): ?array
    {
        return $data === null
            ? null
            : ($this->orm->getEntityRegistry()->getMapper($this->target)?->cast($data) ?? $data);
    }

    public function initReference(Node $node): ReferenceInterface
    {
        $scope = $this->getReferenceScope($node);
        if ($scope === null) {
            $result = new Reference($this->target, []);
            $result->setValue(null);
            return $result;
        }
        return $scope === [] ? new EmptyReference($this->target, null) : new Reference($this->target, $scope);
    }

    /**
     * Resolve the reference to the object.
     */
    public function resolve(ReferenceInterface $reference, bool $load): object|iterable|null
    {
        if ($reference->hasValue()) {
            return $reference->getValue();
        }

        $result = $this->orm->get($reference->getRole(), $reference->getScope(), $load);
        if ($load === true || $result !== null) {
            $reference->setValue($result);
        }
        return $result;
    }

    public function collect(mixed $data): ?object
    {
        return $data;
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
