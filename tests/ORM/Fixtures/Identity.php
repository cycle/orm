<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class Identity
{
    private $id;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}
