<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\Enum;

class EnumUser
{
    public ?int $id = null;
    public ?int $balance = null;
    public TypeStringEnum $enum_string;
    public TypeIntEnum $enum_int;
}
