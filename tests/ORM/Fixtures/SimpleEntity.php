<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class SimpleEntity
{
    public ?int $id = null;
    public string $name = '';
    public ?string $description = null;
    public int $sortOrder = 0;
    public ?object $parent = null;
}
