<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue425\Entity;

class Post
{
    public int $id;
    public string $title;
    /** @var iterable<Comment> */
    public iterable $comments = [];
}
