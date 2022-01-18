<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Enum\Fixture;

class User
{
    public ?int $id = null;
    public string $name;
    public TypeEnum $type = TypeEnum::Guest;
}
