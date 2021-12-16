<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

use Cycle\ORM\Heap\Node;

interface EntityFactoryInterface
{
    /**
     * Create new entity based on given role and input data.
     *
     * @param string $role Entity role.
     * @param array<string, mixed> $data Entity data.
     * @param bool $typecast Indicates that data is raw, and typecasting should be applied.
     */
    public function make(string $role, array $data = [], int $status = Node::NEW, bool $typecast = false): object;
}
