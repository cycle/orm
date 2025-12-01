<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

/**
 * Proxy node that holds pitched nodes.
 *
 * Proxies pitched nodes to parent node.
 *
 * @internal
 */
final class ProxyNode extends AbstractNode
{
    /**
     * @var array<non-empty-string, AbstractNode> Indexed pitched nodes
     */
    private array $includeNodes = [];

    /**
     * @param list<non-empty-string> $innerKeys
     */
    public function __construct(array $innerKeys)
    {
        parent::__construct([], $innerKeys);
    }

    /**
     * Add pitched node to proxy.
     *
     * @param non-empty-string $target
     */
    public function addNode(string $target, AbstractNode $node): AbstractNode
    {
        if (!\array_key_exists($target, $this->includeNodes)) {
            $this->includeNodes[$target] = $node;
            $this->parent->linkNode($this->container, $node);
        }

        return $this->includeNodes[$target];
    }

    protected function push(array &$data): void
    {
        // BelongsToMorphedNode doesn't store data itself
        // Data is pushed to child nodes by role
    }
}
