<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\ORMInterface;
use Spiral\ORM\PromiseInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\RelationInterface;
use Spiral\ORM\Schema;
use Spiral\ORM\Point;

abstract class AbstractRelation implements RelationInterface
{
    use Traits\ContextTrait;

    /**
     * @invisible
     * @var ORMInterface
     */
    protected $orm;

    protected $class;

    protected $relation;

    protected $schema;

    /** @var string */
    protected $innerKey;

    /** @var string */
    protected $outerKey;

    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        $this->orm = $orm;
        $this->class = $class;
        $this->relation = $relation;
        $this->schema = $schema;
        $this->innerKey = $this->define(Relation::INNER_KEY);
        $this->outerKey = $this->define(Relation::OUTER_KEY);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // this is incorrect class
        return sprintf("%s->%s", $this->class, $this->relation);
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
        $item = $this->orm->make($this->class, $data, Point::LOADED);

        return [$item, $item];
    }

    public function initPromise(Point $state, $data): array
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

    protected function getPoint($entity): ?Point
    {
        if (is_null($entity)) {
            return null;
        }

        if ($entity instanceof PromiseInterface) {
            return new Point(
                Point::PROMISED,
                $entity->__scope(),
                $this->orm->getSchema()->define($this->class, Schema::ALIAS)
            );
        }

        $state = $this->orm->getHeap()->get($entity);

        if (is_null($state)) {
            $state = new Point(Point::NEW, [],
                $this->orm->getSchema()->define($this->class, Schema::ALIAS)
            );

            $this->orm->getHeap()->attach($entity, $state);
        }

        return $state;
    }

    protected function getORM(): ORMInterface
    {
        return $this->orm;
    }
}