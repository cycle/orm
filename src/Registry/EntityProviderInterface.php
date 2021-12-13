<?php

declare(strict_types=1);

namespace Cycle\ORM\Registry;

use Cycle\ORM\Heap\Node;

interface EntityProviderInterface
{
    /**
     * Get/load entity by unique key/value pair.
     *
     * @param array  $scope KV pair to locate the model, currently only support one pair.
     */
    public function get(string $role, array $scope, bool $load = true): ?object;

    /**
     * Create new entity based on given role and input data.
     *
     * @param string $role Entity role.
     * @param array<string, mixed> $data Entity data.
     * @param bool $typecast Indicates that data is raw, and typecasting should be applied.
     */
    public function make(string $role, array $data = [], int $status = Node::NEW, bool $typecast = false): object;
}
