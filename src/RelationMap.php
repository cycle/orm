<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

final class RelationMap
{
    private $orm;
    private $relations = [];

    public function __construct(ORMInterface $orm, array $relations)
    {
        $this->orm = $orm;
        $this->relations = $relations;
    }

    public function withContext(array &$data): self
    {
        return clone $this;
    }

    public function getRelation(string $relation)
    {
        // can be promise ?
    }
}