<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Relation\Pivoted\PivotedCollection;

class Tag
{
    public $id;

    public $name;

    public $users;

    public function __construct()
    {
        $this->users = new PivotedCollection();
    }
}
