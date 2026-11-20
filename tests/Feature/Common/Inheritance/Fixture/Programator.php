<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture;

class Programator extends Engineer
{
    public ?int $subrole_id = null;
    public ?int $second_id = null;
    public string $language;
}
