<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

interface EntityProviderInterface
{
    /**
     * Get/load entity by unique key/value pair.
     *
     * @param array $scope KV pair to locate the model, currently only support one pair.
     */
    public function get(string $role, array $scope, bool $load = true): ?object;
}
