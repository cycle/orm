<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue71\Entity;

class Tag
{
    public ?int $id = null;
    public string $name;

    /** @var iterable<Post> */
    public iterable $posts = [];
}
