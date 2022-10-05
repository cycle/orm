<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2\Entity;

use Doctrine\Common\Collections\Collection;

class MarkCriterionResult
{
    public ?string $id = null;
    public int $resultObjective = 0;

    public Student $student;
    public ?string $student_id = null;

    /** @var Collection<array-key, MarkSubcriterionResult> */
    public Collection $markSubcriterionResults;
}
