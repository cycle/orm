<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures\HasOne;

class User
{
    public $id;
    public $email;
    public $balance;

    /** @var Profile */
    public $profile;
}