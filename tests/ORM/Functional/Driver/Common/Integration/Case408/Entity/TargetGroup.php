<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Entity;

use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\TargetGroupId;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case408\Type\TargetGroupName;

class TargetGroup
{
    private ?TargetGroupId $id = null;
    private TargetGroupName $name;

    private iterable $targets = [];
    private iterable $manyTargets = [];

    public function __construct(string $name = '')
    {
        $this->name = TargetGroupName::create($name);
    }

    public function getTargets(): iterable
    {
        return $this->targets;
    }

    public function getManyTargets(): iterable
    {
        return $this->manyTargets;
    }
}
