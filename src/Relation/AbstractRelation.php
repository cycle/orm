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

abstract class AbstractRelation implements RelationInterface
{
    use Traits\ContextTrait;

    /** @var ORMInterface @internal */
    protected $orm;

    /** @var string */
    protected $targetRole;

    /** @var string */
    protected $relation;

    /** @var array */
    protected $schema;

    /** @var string */
    protected $innerKey;

    /** @var string */
    protected $outerKey;

    /**
     * @param ORMInterface $orm
     * @param string       $relation
     * @param string       $target
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $relation, string $target, array $schema)
    {
        $this->orm = $orm;
        $this->targetRole = $target;
        $this->relation = $relation;
        $this->schema = $schema;

        $this->innerKey = $schema[Relation::INNER_KEY];
        $this->outerKey = $schema[Relation::OUTER_KEY];
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->relation;
    }

    /**
     * @inheritdoc
     */
    public function isCascade(): bool
    {
        return $this->schema[Relation::CASCADE] ?? false;
    }

    /**
     * @inheritdoc
     */
    public function init(array $data): array
    {
        $item = $this->orm->make($this->targetRole, $data, Node::MANAGED);

        return [$item, $item];
    }

    /**
     * @inheritdoc
     */
    public function extract($data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $point): array
    {
        return [null, null];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // this is incorrect class
        return sprintf("%s->%s", $this->targetRole, $this->relation);
    }

    /**
     * Indicates that relation can not be nullable.
     *
     * @return bool
     */
    protected function isRequired(): bool
    {
        if (array_key_exists(Relation::NULLABLE, $this->schema)) {
            return !$this->schema[Relation::NULLABLE];
        }

        return true;
    }

    /**
     * Get Node for the given entity. Null if entity does not exists. Automatically
     * register entity claims.
     *
     * @param object $entity
     * @param int    $claim
     * @return Node|null
     */
    protected function getNode($entity, int $claim = 0): ?Node
    {
        if (is_null($entity)) {
            return null;
        }

        if ($entity instanceof PromiseInterface) {
            return new Node(Node::PROMISED, $entity->__scope(), $entity->__role());
        }

        $node = $this->orm->getHeap()->get($entity);

        if (is_null($node)) {
            $node = new Node(Node::NEW, [], $this->orm->getMapper($entity)->getRole());
            $this->orm->getHeap()->attach($entity, $node);
        }

        if ($claim === 1) {
            $node->getState()->addClaim();
        }

        if ($claim === -1) {
            $node->getState()->decClaim();
        }

        return $node;
    }

    /**
     * @return ORMInterface
     */
    protected function getORM(): ORMInterface
    {
        return $this->orm;
    }

    /**
     * @deprecated
     * @param $key
     * @return mixed|null
     */
    protected function define($key)
    {
        return $this->schema[$key] ?? null;
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