<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures\Mapper;

class UserEntity
{
    public $id;
    public $email;
    public $balance;

    /** @var ProfileEntity */
    public $profile;
}