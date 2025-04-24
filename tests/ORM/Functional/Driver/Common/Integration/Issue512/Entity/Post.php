<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue512\Entity;

class Post
{
    public ?int $id = null;
    public string $title = '';
    public string $content = '';

    /** @var iterable<Tag> */
    public iterable $tags = [];

    public ?int $tag_id = null;

    public function __construct(string $title = '', string $content = '')
    {
        $this->title = $title;
        $this->content = $content;
    }
}
