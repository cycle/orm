<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

use Cycle\ORM\RelationMap;

interface RelationProviderInterface
{
    /**
     * Get relation map associated with given entity role.
     */
    public function getRelationMap(string $entity): RelationMap;
}
