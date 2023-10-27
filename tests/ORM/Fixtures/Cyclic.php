<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class Cyclic
{
    public $name;

    /** @var Cyclic|null */
    public $cyclic;

    /** @var Cyclic|null */
    public $other;


    public iterable $collection = [];

    public function __construct(string $name = '', ?self $parent = null, ?self $other = null)
    {
        $this->name = $name;
        $this->cyclic = $parent;
        $this->other = $other;
    }
}
