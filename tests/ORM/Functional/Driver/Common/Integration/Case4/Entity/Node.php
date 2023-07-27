<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case4\Entity;

class Node
{
    private int $id;

    private string $key;

    private ?self $parent = null;
}
