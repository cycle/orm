<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Doctrine\Common\Collections\ArrayCollection;

class CompositePK
{
    public $key1;
    public $key2;
    public $key3;
    public $key4;

    public $child_entity;

    public $child_key1;
    public $child_key2;
    public $child_key3;
    public $child_key4;

    public $children;
    public $pivoted;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->pivoted = new PivotedCollection();
    }
}
