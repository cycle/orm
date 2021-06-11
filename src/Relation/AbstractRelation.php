<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Select\SourceInterface;
use JetBrains\PhpStorm\ExpectedValues;

abstract class AbstractRelation implements RelationInterface
{
    use Relation\Traits\NodeTrait;

    /** @internal */
    protected ORMInterface $orm;

    protected string $name;

    protected string $target;

    protected array $schema;

    /** @var string[] */
    protected array $innerKeys;

    /** @var string[] */
    protected array $outerKeys;

    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        $this->orm = $orm;
        $this->name = $name;
        $this->target = $target;
        $this->schema = $schema;
        $this->innerKeys = (array)$schema[Relation::INNER_KEY];
        $this->outerKeys = (array)$schema[Relation::OUTER_KEY];
    }

    public function getInnerKeys(): array
    {
        return $this->innerKeys;
    }

    public function __toString(): string
    {
        // this is incorrect class
        return sprintf('`%s` (%s)->%s', $this->name, get_class($this), $this->target);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function isCascade(): bool
    {
        return $this->schema[Relation::CASCADE] ?? false;
    }

    public function init(Node $node, array $data): array
    {
        $item = $this->orm->make($this->target, $data, Node::MANAGED);

        return [$item, $item];
    }

    public function extract($data)
    {
        return $data;
    }

    protected function isNullable(): bool
    {
        return !empty($this->schema[Relation::NULLABLE]);
    }

    /**
     * Get the source associated with the role.
     */
    protected function getSource(string $role = null): SourceInterface
    {
        return $this->orm->getSource($role ?? $this->target);
    }

    /**
     * Assert that given entity is allowed for the relation.
     *
     * @throws RelationException
     */
    protected function assertValid(Node $related): void
    {
        if ($related->getRole() != $this->target) {
            throw new RelationException(sprintf('Unable to link %s, given `%s`.', $this, $related->getRole()));
        }
    }

    /**
     * Resolve the reference to the object.
     *
     * @return mixed|null
     */
    protected function resolve(ReferenceInterface $reference)
    {
        if ($reference instanceof PromiseInterface) {
            return $reference->__resolve();
        }

        return $this->orm->get($reference->__role(), $reference->__scope(), true);
    }

    protected function isResolved(ReferenceInterface $reference): bool
    {
        if ($reference instanceof PromiseInterface) {
            return $reference->__loaded();
        }
        return false;
    }
}
