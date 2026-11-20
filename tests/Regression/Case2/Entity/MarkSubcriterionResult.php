<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Case2\Entity;

use Doctrine\Common\Collections\Collection;

class MarkSubcriterionResult
{
    public ?string $id = null;
    public int $resultObjective = 0;
    public Student $student;
    public ?string $student_id = null;

    /** @var Collection<array-key, MarkAspectResult> */
    public Collection $markAspectResults;
}
