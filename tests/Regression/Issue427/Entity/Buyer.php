<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue427\Entity;

class Buyer
{
    public array $partners = [];

    public function __construct(
        public int $id,
        public string $address,
    ) {}
}
