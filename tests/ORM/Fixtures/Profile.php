<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class Profile
{
    public $id;
    public $image;

    /** @var Nested */
    public $nested;

    /** @var User|null */
    public $user;

    public function getID()
    {
        return $this->id;
    }

    public function getImage()
    {
        return $this->image;
    }
}
