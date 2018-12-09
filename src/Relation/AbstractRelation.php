<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Exception\RelationException;
use Spiral\ORM\Mapper\SelectableInterface;
use Spiral\ORM\Node;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\PromiseInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\RelationInterface;
use Spiral\ORM\Schema;

abstract class AbstractRelation implements RelationInterface
{
    use Traits\ContextTrait;

    /**
     * @invisible
     * @var ORMInterface
     */
    protected $orm;

    protected $targetRole;

    protected $relation;

    protected $schema;

    /** @var string */
    protected $innerKey;

    /** @var string */
    protected $outerKey;

    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        $this->orm = $orm;
        $this->targetRole = $class;
        $this->relation = $relation;
        $this->schema = $schema;

        $this->innerKey = $this->define(Relation::INNER_KEY);
        $this->outerKey = $this->define(Relation::OUTER_KEY);
    }

    public function getName(): string
    {
        return $this->relation;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // this is incorrect class
        return sprintf("%s->%s", $this->targetRole, $this->relation);
    }

    public function isRequired(): bool
    {
        if (array_key_exists(Relation::NULLABLE, $this->schema)) {
            return !$this->schema[Relation::NULLABLE];
        }

        return true;
    }

    public function isCascade(): bool
    {
        return $this->schema[Relation::CASCADE] ?? false;
    }

    public function init($data): array
    {
        $item = $this->orm->make($this->targetRole, $data, Node::MANAGED);

        return [$item, $item];
    }

    public function initPromise(Node $point): array
    {
        return [null, null];
    }

    public function extract($data)
    {
        return $data;
    }

    protected function define($key)
    {
        return $this->schema[$key] ?? null;
    }

    protected function getNode($entity, int $claim = 0): ?Node
    {
        if (is_null($entity)) {
            return null;
        }

        if ($entity instanceof PromiseInterface) {
            return new Node(
                Node::PROMISED,
                $entity->__scope(),
                $this->orm->getSchema()->define($this->targetRole, Schema::ALIAS)
            );
        }

        $state = $this->orm->getHeap()->get($entity);

        if (is_null($state)) {
            $state = new Node(Node::NEW, [],
                $this->orm->getSchema()->define($this->targetRole, Schema::ALIAS)
            );

            $this->orm->getHeap()->attach($entity, $state);
        }

        if ($claim === 1) {
            $state->getState()->addClaim();
        }

        if ($claim === -1) {
            $state->getState()->decClaim();
        }

        return $state;
    }

    protected function getORM(): ORMInterface
    {
        return $this->orm;
    }

    /**
     * Get selectable mapper associated with a role.
     *
     * @param string|null $role
     * @return SelectableInterface
     *
     * @throws RelationException
     */
    protected function getMapper(string $role = null): SelectableInterface
    {
        $mapper = $this->orm->getMapper($role ?? $this->targetRole);
        if (!$mapper instanceof SelectableInterface) {
            throw new RelationException("Relation {$this} can only with with SelectableInterface mappers");
        }

        return $mapper;
    }
}