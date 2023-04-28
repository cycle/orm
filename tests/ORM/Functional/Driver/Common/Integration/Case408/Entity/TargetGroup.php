<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Entity;

use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\TargetGroupId;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\TargetGroupName;

class TargetGroup
{
    public ?TargetGroupId $id = null;
    public TargetGroupName $name;

    public function __construct(string $name = '')
    {
        $this->name = TargetGroupName::create($name);
    }
}
