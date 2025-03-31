<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue511\Entity;

class User
{
    public ?int $id = null;
    public string $login;
    public ?Passport $passport = null;
    public ?VisitPermission $visitPermission = null;
}
