<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Case2\Entity;

class StudentProgress
{
    public ?string $student_id = null;

    public function __construct(
        public ?string $id = null,
        public int $aspectsEnteredCount = 0,
    ) {}
}
