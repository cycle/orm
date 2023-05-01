<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type;

use Stringable;

interface ValueObjectInterface extends Stringable
{
    public static function create(string $id): self;
}
