<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue428\Entity;

class Metadata
{
    public function __construct(
        public string $data = '',
    ) {}
}
