<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;

/**
 * @internal
 */
abstract class AbstractRelation implements ActiveRelationInterface, \Stringable
{
    /**
     * Additional target roles: class-name of the primary role, roles and classes of primary role parents if the primary
     * role has parents
     *
     * @var class-string[]|string[]
     */
    protected array $targets = [];

    /** @var string[] */
    protected array $innerKeys;

    /** @var string[] */
    protected array $outerKeys;

    protected ?string $inversion;
    protected MapperProviderInterface $mapperProvider;
    protected SchemaInterface $ormSchema;

    /**
     * @param string $target Primary target role
     */
    public function __construct(
        ORMInterface $orm,
        private string $role,
        protected string $name,
        protected string $target,
        protected array $schema,
    ) {
        $this->ormSchema = $orm->getSchema();
        $this->mapperProvider = $orm->getService(MapperProviderInterface::class);
        $this->innerKeys = (array) $schema[Relation::INNER_KEY];
        $this->outerKeys = (array) $schema[Relation::OUTER_KEY];
        $this->inversion = $schema[Relation::INVERSION] ?? null;
    }

    public function getInnerKeys(): array
    {
        return $this->innerKeys;
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

    public function __toString(): string
    {
        // this is incorrect class
        return \sprintf('`%s` (%s)->%s', $this->name, $this::class, $this->target);
    }

    protected function isNullable(): bool
    {
        // return $this->schema[Relation::NULLABLE] ?? false;
        return !empty($this->schema[Relation::NULLABLE]);
    }

    protected function getTargetRelationName(): string
    {
        return $this->inversion ?? $this->role . '.' . $this->name . ':' . $this->target;
    }

    /**
     * Assert that given entity is allowed for the relation.
     *
     * @throws RelationException
     */
    protected function assertValid(Node $related): void
    {
        if ($related->getRole() === $this->target || \in_array($related->getRole(), $this->targets, true)) {
            return;
        }
        $role = $this->ormSchema->resolveAlias($related->getRole());
        if ($role === $this->target) {
            $this->targets[] = $related->getRole();
            return;
        }
        // Check parents
        do {
            $parent = $this->ormSchema->define($role, SchemaInterface::PARENT);
            if ($parent === $this->target) {
                $this->targets[] = $related->getRole();
                return;
            }
            $role = $parent;
        } while ($parent !== null);
        throw new RelationException(\sprintf('Unable to link %s, given `%s`.', (string) $this, $related->getRole()));
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
