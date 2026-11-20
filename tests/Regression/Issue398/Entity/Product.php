<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue398\Entity;

class Product
{
    public const ROLE = 'product';

    public int $id;

    public function __construct(
        public string $title,
    ) {}
}
