<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Fixture;

class Programator extends Engineer
{
    public ?int $subrole_id = null;

    public string $language;
}
