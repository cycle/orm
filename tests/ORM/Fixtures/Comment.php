<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures;

class Comment
{
    public $id;

    public $message;

    /** @var User */
    public $user;
}