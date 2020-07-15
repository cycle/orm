<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\CyclicRef;

class User
{
    public $id;
    public $email;

    public $created_at;
    public $updated_at;
}
