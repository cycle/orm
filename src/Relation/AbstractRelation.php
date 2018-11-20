<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\RelationInterface;
use Spiral\ORM\State;

abstract class AbstractRelation implements RelationInterface
{
    use Relation\Traits\PromiseTrait;

    public const COLLECTION = false;

    /**
     * @invisible
     * @var ORMInterface
     */
    protected $orm;

    protected $class;

    protected $relation;

    protected $schema;

    public function __construct(
        ORMInterface $orm,
        string $class,
        string $relation,
        array $schema
    ) {
        $this->orm = $orm;
        $this->class = $class;
        $this->relation = $relation;
        $this->schema = $schema;
    }

    public function isCascade(): bool
    {
        return $this->schema[Relation::CASCADE] ?? false;
    }

    public function isCollection(): bool
    {
        return static::COLLECTION;
    }

    public function init($data)
    {
        return $this->orm->make($this->class, $data, State::LOADED);
    }

    public function initArray(array $data)
    {
        $result = [];
        foreach ($data as $item) {
            $result[] = $this->init($item);
        }

        return $result;
    }

    public function wrapCollection($data)
    {
        return new ArrayCollection($data);
    }

    public function extract($relData)
    {
        return $relData;
    }

    protected function define(string $key)
    {
        return $this->schema[$key] ?? null;
    }

    protected function getState($entity): ?State
    {
        return $this->orm->getHeap()->get($entity);
    }

    public function __toString()
    {
        return sprintf("%s->%s", $this->class, $this->relation);
    }
}