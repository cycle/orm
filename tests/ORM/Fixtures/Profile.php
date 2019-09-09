<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class Profile
{
    public $id;
    public $image;

    /** @var Nested */
    public $nested;

    /** @var User */
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
