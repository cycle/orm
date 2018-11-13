<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures;

class Profile
{
    public $id;
    public $image;

    /** @var Nested */
    public $nested;

    /** @var User */
    public $user;
}