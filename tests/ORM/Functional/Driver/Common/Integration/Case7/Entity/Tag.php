<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity;

class Tag
{
    public ?int $id = null;
    public string $label;

    public function __construct(string $label)
    {
        $this->label = $label;
    }
}
