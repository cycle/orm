<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue511\Entity;

class Passport
{
    public ?int $id = null;
    public string $number;
    public User $user;
}
