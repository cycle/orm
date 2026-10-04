<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue71\Entity;

class Post
{
    public ?int $id = null;
    public string $title;

    /** @var iterable<Tag> */
    public iterable $tags = [];

    /** @var iterable<Comment> */
    public iterable $comments = [];
}
