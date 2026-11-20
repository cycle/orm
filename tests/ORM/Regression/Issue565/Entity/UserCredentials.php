<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue565\Entity;

class UserCredentials
{
    public string $username = '';
    public string $password = '';
    public int $numLogins = 0;
}
