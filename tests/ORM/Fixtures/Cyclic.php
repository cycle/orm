<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class Cyclic
{
    public $name;

    /** @var Cyclic|null */
    public $cyclic;

    /** @var Cyclic|null */
    public $other;

    public function __construct(string $name = '', ?Cyclic $parent = null, ?Cyclic $other = null)
    {
        $this->name = $name;
        $this->cyclic = $parent;
        $this->other = $other;
    }
}
