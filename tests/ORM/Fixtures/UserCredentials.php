<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class UserCredentials
{
    public $username;
    public $password;

    public $unmapped_public_property;
    private $unmapped_private_property;
}
