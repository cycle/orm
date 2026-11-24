<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue408\Entity;

use Cycle\ORM\Tests\Regression\Issue408\Type\Hash;
use Cycle\ORM\Tests\Regression\Issue408\Type\TargetGroupId;
use Cycle\ORM\Tests\Regression\Issue408\Type\TargetId;

class Pivot
{
    protected TargetId $targetId;
    protected TargetGroupId $targetGroupId;
    protected Hash $hash;

    public function __construct(int $targetId, int $targetGroupId)
    {
        $this->targetId = TargetId::create($targetId);
        $this->targetGroupId = TargetGroupId::create($targetGroupId);
        $this->hash = Hash::create($targetId . '/' . $targetGroupId);
    }
}
