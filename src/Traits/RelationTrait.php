<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Traits;

trait RelationTrait
{
    private $relations = [];

    // todo: store original set of relations (YEEEEAH BOYYYY)
    public function setRelation(string $name, $context)
    {
        $this->relations[$name] = $context;

        // todo: i don't like this (!)
        unset($this->data[$name]);
    }

    public function hasRelation(string $name)
    {
        return array_key_exists($name, $this->relations);
    }

    public function getRelation(string $name)
    {
        return $this->relations[$name] ?? null;
    }
}