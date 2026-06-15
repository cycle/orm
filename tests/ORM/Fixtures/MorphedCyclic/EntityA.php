<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\MorphedCyclic;

class EntityA implements MorphedInterface
{
    public $id;
    public $name;
    public $parentId;
    public $parentType;

    /** @var MorphedInterface|null */
    public $parent;
}
