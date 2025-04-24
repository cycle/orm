<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue512\Entity;

class Tag
{
    public ?int $id = null;
    public string $label;

    /** @var iterable<Post> */
    public iterable $posts = [];

    public function __construct(string $label)
    {
        $this->label = $label;
    }
}
