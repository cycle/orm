<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\SourceProviderInterface;

/**
 * Embeds one object to another.
 */
final class Embedded implements RelationInterface
{
    use Relation\Traits\NodeTrait;

    /** @var ORMInterface|SourceProviderInterface @internal */
    protected $orm;

    /** @var string */
    protected $name;

    /** @var string */
    protected $target;

    /** @var array */
    protected $schema;

    /** @var MapperInterface */
    protected $mapper;

    /** @var string */
    protected $primaryKey;

    /** @var array */
    protected $columns = [];

    /**
     * @param ORMInterface $orm
     * @param string       $name
     * @param string       $target
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        $this->orm = $orm;
        $this->name = $name;
        $this->target = $target;
        $this->schema = $schema;
        $this->mapper = $this->orm->getMapper($target);

        // this relation must manage column association manually, bypassing related mapper
        $this->primaryKey = $this->orm->getSchema()->define($target, Schema::PRIMARY_KEY);
        $this->columns = $this->orm->getSchema()->define($target, Schema::COLUMNS);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @inheritDoc
     */
    public function isCascade(): bool
    {
        // always cascade
        return true;
    }

    /**
     * @inheritdoc
     */
    public function init(Node $node, array $data): array
    {
        // ensure proper object reference
        $data[$this->primaryKey] = $node->getData()[$this->primaryKey];

        $item = $this->orm->make($this->target, $data, Node::MANAGED);

        return [$item, $item];
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        $primaryKey = $this->fetchKey($parentNode, $this->primaryKey);
        if (empty($primaryKey)) {
            return [null, null];
        }

        /** @var ORMInterface $orm */
        $orm = $this->orm;

        $e = $orm->getHeap()->find($this->target, [$this->primaryKey => $primaryKey]);
        if ($e !== null) {
            return [$e, $e];
        }

        $r = $this->orm->promise($this->target, [$this->primaryKey => $primaryKey]);
        return [$r, [$this->primaryKey => $primaryKey]];
    }

    /**
     * @inheritdoc
     */
    public function extract($data)
    {
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function queue(CC $store, $entity, Node $node, $related, $original): CommandInterface
    {
        if ($related instanceof ReferenceInterface) {
            if ($related->__scope() === $original) {
                if (!($related instanceof PromiseInterface && $related->__loaded())) {
                    // do not update non resolved and non changed promises
                    return new Nil();
                }

                $related = $this->resolve($related);
            } else {
                // do not affect parent embeddings
                $related = clone $this->resolve($related);
            }
        }

        if ($related === null) {
            throw new NullException("Embedded relation `{$this->name}` can't be null");
        }

        $state = $this->getNode($related)->getState();

        // calculate embedded node changes
        $changes = $this->getChanges($related, $state);

        // register node changes
        $state->setData($changes);

        // store embedded entity changes via parent command
        foreach ($this->mapColumns($changes) as $key => $value) {
            $store->register($key, $value, true);
        }

        // currently embeddings does not support chain relations, however it is possible by
        // exposing relationMap inside this method. in theory it is possible to use
        // parent entity command to carry context for nested relations, however, custom context
        // propagation chain must be defined (embedded node => parent command)
        // in short, we need to get access to getRelationMap from orm to support it.

        return new Nil();
    }

    /**
     * @param mixed $related
     * @param State $state
     * @return array
     */
    protected function getChanges($related, State $state): array
    {
        $data = $this->mapper->extract($related);

        return array_udiff_assoc($data, $state->getData(), [static::class, 'compare']);
    }

    /**
     * Map internal field names to database specific column names.
     *
     * @param array $columns
     * @return array
     */
    protected function mapColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $column => $value) {
            if (array_key_exists($column, $this->columns)) {
                $result[$this->columns[$column]] = $value;
            } else {
                $result[$column] = $value;
            }
        }

        return $result;
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @return int
     */
    protected static function compare($a, $b): int
    {
        if ($a == $b) {
            return 0;
        }

        return ($a > $b) ? 1 : -1;
    }

    /**
     * Resolve the reference to the object.
     *
     * @param ReferenceInterface $reference
     * @return mixed|null
     */
    protected function resolve(ReferenceInterface $reference)
    {
        if ($reference instanceof PromiseInterface) {
            return $reference->__resolve();
        }

        return $this->orm->get($reference->__role(), $reference->__scope(), true);
    }

    /**
     * Fetch key from the state.
     *
     * @param Node   $state
     * @param string $key
     * @return mixed|null
     */
    protected function fetchKey(?Node $state, string $key)
    {
        if ($state === null) {
            return null;
        }

        return $state->getData()[$key] ?? null;
    }
}
