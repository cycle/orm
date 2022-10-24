<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case3\Entity;

class Currency
{
    public function __construct(
        public string $code,
        public string $name
    ) {
    }
}
