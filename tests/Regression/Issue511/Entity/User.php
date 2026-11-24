<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue511\Entity;

class User
{
    public ?int $id = null;
    public string $login;
    public ?Passport $passport = null;
    public ?VisitPermission $visitPermission = null;
}
