<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class UserCredentials
{
    public $username;
    public $password;

    public $unmapped_public_property;
    private $unmapped_private_property;
}
