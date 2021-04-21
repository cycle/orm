<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;

class CompositePK
{
    public $key1;
    public $key2;
    public $key3;
    public $key4;

    public $child_entity;

    public $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}
