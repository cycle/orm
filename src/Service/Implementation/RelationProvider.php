<?php

declare(strict_types=1);

namespace Cycle\ORM\Service\Implementation;

use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Service\RelationProviderInterface;
use Cycle\ORM\RelationMap;

/**
 * @internal
 */
final class RelationProvider implements RelationProviderInterface
{
    /** @var array<non-empty-string, RelationMap> */
    private array $relMaps = [];

    public function __construct(
        private ?ORMInterface $orm,
    ) {}

    /**
     * Get relation map associated with the given class.
     */
    public function getRelationMap(string $entity): RelationMap
    {
        if (isset($this->relMaps[$entity])) {
            return $this->relMaps[$entity];
        }
        if ($this->orm === null) {
            throw new ORMException('Relation Map is not prepared.');
        }

        return $this->relMaps[$entity] = RelationMap::build($this->orm, $entity);
    }

    public function prepareRelationMaps(): void
    {
        if ($this->orm === null) {
            return;
        }
        foreach ($this->orm->getSchema()->getRoles() as $role) {
            $this->getRelationMap($role);
        }
        $this->orm = null;
    }
}
