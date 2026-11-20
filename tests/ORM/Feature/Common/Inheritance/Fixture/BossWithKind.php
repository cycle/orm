<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture;

class BossWithKind extends WorkerWithKind
{
    public ?int $level = null;
}
