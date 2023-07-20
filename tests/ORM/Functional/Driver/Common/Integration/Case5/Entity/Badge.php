<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case5\Entity;

class Badge
{
    public function __construct(
        public int $id,
        public string $label,
    ) {
    }
}
