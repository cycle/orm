<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;

class Group
{
    public $id;

    public $name;

    /**
     * @var ArrayCollection
     */
    public $users;
}
