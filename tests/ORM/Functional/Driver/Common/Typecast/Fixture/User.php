<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

class User
{
    public ?Wrapper $id = null;
    public string $email;
    public Wrapper $balance;
    public iterable $books = [];
}
