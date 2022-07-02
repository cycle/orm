<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

class EnumedUser
{
    public ?int $id = null;
    public ?int $balance = null;
    public mixed $enum_type;
}
