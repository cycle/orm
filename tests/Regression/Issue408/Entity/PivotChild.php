<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue408\Entity;

use Cycle\ORM\Tests\Regression\Issue408\Type\Rate;

class PivotChild extends Pivot
{
    private Rate $rate;

    public function __construct(int $rate)
    {
        $this->rate = Rate::create($rate);
    }
}
