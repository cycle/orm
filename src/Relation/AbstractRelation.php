<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\SourceInterface;

abstract class AbstractRelation implements ActiveRelationInterface
{
    /** @internal */
    protected ORMInterface $orm;

    protected string $name;

    /**
     * Primary target role
     */
    protected string $target;

    /**
     * Additional target roles: class-name of the primary role, roles and classes of primary role parents if the primary
     * role has parents
     *
     * @var string[]|class-string[]
     */
    protected array $targets = [];

    protected array $schema;

    /** @var string[] */
    protected array $innerKeys;

    /** @var string[] */
    protected array $outerKeys;

    private string $role;

    public function __construct(ORMInterface $orm, string $role, string $name, string $target, array $schema)
    {
        $this->orm = $orm;
        $this->role = $role;
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

    protected function isNullable(): bool
    {
        return !empty($this->schema[Relation::NULLABLE]);
    }

    protected function getTargetRelationName(): string
    {
        return $this->role . '.' . $this->name . ':' . $this->target;
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
        if ($related->getRole() === $this->target || in_array($related->getRole(), $this->targets, true)) {
            return;
        }
        $role = $this->orm->getSchema()->resolveAlias($related->getRole());
        if ($role === $this->target) {
            $this->targets[] = $related->getRole();
            return;
        }
        // Check parents
        do {
            $parent = $this->orm->getSchema()->define($role, SchemaInterface::PARENT);
            if ($parent === $this->target) {
                $this->targets[] = $related->getRole();
                return;
            }
            $role = $parent;
        } while ($parent !== null);
        throw new RelationException(sprintf('Unable to link %s, given `%s`.', (string)$this, $related->getRole()));
    }

    protected function registerWaitingFields(State $state, bool $required = true): void
    {
        foreach ($this->innerKeys as $key) {
            $state->waitField($key, $required);
        }
    }

    protected function compareReferences(ReferenceInterface $original, mixed $related): bool
    {
        if ($original === $related) {
            return true;
        }
        if ($related instanceof ReferenceInterface) {
            return $related->getScope() === $original->getScope();
        }
        return false;
    }
}
