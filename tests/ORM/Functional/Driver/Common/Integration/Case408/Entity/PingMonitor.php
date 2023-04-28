<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Entity;

use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\MonitorInterval;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\PingMonitorId;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\PublicHostname;

class PingMonitor
{
    public ?PingMonitorId $id = null;
    public PublicHostname $hostname;
    public MonitorInterval $monitorInterval;

    public function __construct(string $hostname = '', int $monitorInterval = 5)
    {
        $this->hostname = PublicHostname::create($hostname);
        $this->monitorInterval = MonitorInterval::create($monitorInterval);
    }
}
