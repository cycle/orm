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
use Cycle\ORM\Heap\Node;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation\Embedded\PartialPromise;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\SourceProviderInterface;

/**
 * Embeds one object to another.
 */
final class Embedded implements RelationInterface
{
    /** @var ORMInterface|SourceProviderInterface @internal */
    protected $orm;

    /** @var string */
    protected $name;

    /** @var string */
    protected $target;

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
     */
    public function __construct(ORMInterface $orm, string $name, string $target)
    {
        $this->orm = $orm;
        $this->name = $name;
        $this->target = $target;
        $this->mapper = $this->orm->getMapper($target);

        // this relation must manage column association manually, bypassing related mapper
        $this->primaryKey = $this->orm->getSchema()->define($target, Schema::PRIMARY_KEY);
        $this->columns = $this->orm->getSchema()->define($target, Schema::COLUMNS);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function isCascade(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function init(array $data): array
    {
        list($e, $data) = $this->mapper->init($data);
        $this->mapper->hydrate($e, $data);

        return [$e, $data];
    }

    /**
     * @inheritDoc
     */
    public function extract($value)
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function initPromise(Node $node): array
    {
        $primaryKey = $node->getData()[$this->primaryKey] ?? null;
        if (empty($primaryKey)) {
            // unable to initiate promise
            return [null, null];
        }

        $p = new PartialPromise($this->orm, $this->target, [$this->primaryKey => $primaryKey]);
        if ($this->orm->getProxyFactory() !== null) {
            $p = $this->orm->getProxyFactory()->proxy($this->orm, $p);
        }

        return [$p, $p];
    }

    /**
     * @inheritDoc
     */
    public function queue(CC $store, $entity, Node $node, $related, $original): CommandInterface
    {
        if ($related instanceof ReferenceInterface && $original instanceof ReferenceInterface) {
            // todo: need additional logic here
            if ($related === $original) {
                // nothing to do
                return new Nil();
            }
        }

        // todo: promised

        //if ($original instanceof ReferenceInterface) {
        //                    $original = $this->resolve($original);
        //      }

        $changes = $this->getChanges($related, $original);

        // store embedded entity changes via parent command
        foreach ($this->mapColumns($changes) as $key => $value) {
            $store->register($key, $value, true);
        }

        return new Nil();
    }

    /**
     * @param $related
     * @param $original
     * @return array
     */
    protected function getChanges($related, $original): array
    {
        // entity has been reset, nullify all the fields
        if ($related === null) {
            // todo: check if relation can be nullable?
            return array_fill_keys($this->columns, null);
        }

        $data = $this->mapper->extract($related);
        if ($original === null) {
            // nothing were set
            return $data;
        }

        return array_udiff_assoc($data, $original, [static::class, 'compare']);
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
}