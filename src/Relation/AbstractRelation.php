<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\RelationInterface;
use Spiral\ORM\State;

abstract class AbstractRelation implements RelationInterface
{
    public const LEADING    = false;
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

    public function isLeading(): bool
    {
        return static::LEADING;
    }

    public function isCollection(): bool
    {
        return static::COLLECTION;
    }

    public function init($data)
    {
        if (is_null($data)) {
            return null;
        }

        // todo: array?
        // todo: pretty easy?

        return $this->orm->make($this->class, $data, State::LOADED);
    }

    protected function getRelated($entity)
    {
        // todo: move into RelationMap
        return $this->orm->getMapper($this->class)->getField($entity, $this->relation);
    }

    protected function define(string $key)
    {
        return $this->schema[$key] ?? null;
    }

    public function __toString()
    {
        return sprintf("%s->%s", $this->class, $this->relation);
    }
}