<?php

declare(strict_types=1);

namespace Cycle\ORM\Promise;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Relation\RelationInterface;
use RuntimeException;
use WeakReference;

final class DeferredRelation implements Deferred
{
    /** @var WeakReference<Node>|null */
    private ?WeakReference $node;

    private ?RelationInterface $relation;

    private $data;

    /**
     * @var null|callable
     */
    private $dataFactory;
    private Node $nodeCopy;

    public function __construct(
        RelationInterface $relation,
        Node $parentNode,
        $defaultData = null,
        callable $dataFactory = null
    ) {
        $this->node = WeakReference::create($parentNode);
        $this->nodeCopy = $parentNode;
        $this->relation = $relation;
        $this->dataFactory = $dataFactory;
        $this->data = $defaultData;
    }

    public function isLoaded(): bool
    {
        return $this->relation === null;
    }

    private function load(): void
    {
        // if ($this->relation !== null || $this->node->get() === null) {
        if ($this->relation === null || $this->nodeCopy === null) {
            return;
        }
        /** @var Node $node */
        $node = $this->node->get();
        if ($node === null) {
            throw new RuntimeException('Parent Node not found.');
        }
        $deferred = $this->relation->initDeferred($node);
        $this->node = null;
        $this->relation = null;
        if ($deferred instanceof self) {
            // throw new RuntimeException('Scope not resolved.');
            return;
        }
        $this->data = $deferred->getData();
    }

    public function getData(bool $autoload = true)
    {
        $data = $this->getOrigin($autoload);
        return $this->dataFactory === null ? $data : ($this->dataFactory)($data);
    }

    public function getOrigin(bool $autoload = true)
    {
        $this->load();
        return $this->data;
    }

    // public function getScope(): array
    // {
    //     // todo
    // }
    //
    // public function getRole(): string
    // {
    //     // todo
    // }
}
