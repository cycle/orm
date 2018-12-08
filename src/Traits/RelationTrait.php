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
    /** @var array */
    private $relations = [];

    /**
     * @param string $name
     * @param mixed  $context
     */
    public function setRelation(string $name, $context)
    {
        $this->relations[$name] = $context;

        // todo: is it good approach?
        unset($this->data[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasRelation(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getRelation(string $name)
    {
        return $this->relations[$name] ?? null;
    }
}