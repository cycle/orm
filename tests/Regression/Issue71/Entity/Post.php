<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue71\Entity;

class Post
{
    public ?int $id = null;
    public string $title;

    /** @var iterable<Tag> */
    public iterable $tags = [];

    /** @var iterable<Comment> */
    public iterable $comments = [];
}
