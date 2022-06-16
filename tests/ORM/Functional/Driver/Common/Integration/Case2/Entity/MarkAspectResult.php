<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2\Entity;

class MarkAspectResult
{
    public Student $student;

    public MarkSubcriterionResult $markSubcriterionResult;
    public bool $marksRequiresAttention = false;
    public ?string $student_id = null;
    public ?string $mark_subcriterion_result_id = null;

    public function __construct(
        public ?string $id = null,
    ) {
    }
}
