<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Reference\Reference;

class UserID extends Reference
{
    public function __construct($id)
    {
        $this->role = 'user';
        $this->scope = ['id' => $id];
    }
}
