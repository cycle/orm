<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry\Implementation;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Registry\RelationProviderInterface;
use Cycle\ORM\RelationMap;

/**
 * @internal
 */
final class RelationProvider implements RelationProviderInterface
{
    /** @var array<non-empty-string, RelationMap> */
    private array $relMaps = [];

    public function __construct(
        private ORMInterface $orm,
    ) {
    }

    /**
     * Get relation map associated with the given class.
     */
    public function getRelationMap(string $entity): RelationMap
    {
        return $this->relMaps[$entity] ?? ($this->relMaps[$entity] = RelationMap::build($this->orm, $entity));
    }
}
