<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Student
{
    public ?string $id = null;
    public string $firstName;

    /** @var Collection<array-key, MarkAspectResult> */
    public Collection $markAspectResults;

    /** @var Collection<array-key, MarkAspectResult> */
    public Collection $markAspectResultsWhoRequiresAttention;

    /** @var Collection<array-key, StudentProgress> */
    public Collection $studentProgresses;

    public function __construct() {
        $this->markAspectResults = new ArrayCollection();
        $this->markAspectResultsWhoRequiresAttention = new ArrayCollection();
        $this->studentProgresses = new ArrayCollection();
    }
}
