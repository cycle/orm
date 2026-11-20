<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Case3\Entity;

class Currency
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}
}
