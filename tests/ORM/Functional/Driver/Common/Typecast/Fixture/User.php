<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

use DateTimeInterface;

class User
{
    public ?Wrapper $id = null;
    public string $email;
    public Wrapper $balance;
    public iterable $books = [];
    public ?DateTimeInterface $created_at = null;
}
