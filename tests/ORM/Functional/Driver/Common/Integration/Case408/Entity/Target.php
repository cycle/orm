<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Entity;

use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\MonitorName;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\TargetGroupId;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\TargetId;

class Target
{
    public ?TargetId $id = null;
    public TargetGroupId $target_group_id;
    public MonitorName $monitorName;

    public function __construct(string $monitorName = '')
    {
        $this->monitorName = MonitorName::create($monitorName);
    }
}
