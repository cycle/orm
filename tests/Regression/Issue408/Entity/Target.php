<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue408\Entity;

use Cycle\ORM\Tests\Regression\Issue408\Type\MonitorName;
use Cycle\ORM\Tests\Regression\Issue408\Type\TargetGroupId;
use Cycle\ORM\Tests\Regression\Issue408\Type\TargetId;

class Target
{
    protected ?TargetId $id = null;
    private TargetGroupId $target_group_id;
    private MonitorName $monitorName;

    public function __construct(string $monitorName = '')
    {
        $this->monitorName = MonitorName::create($monitorName);
    }
}
