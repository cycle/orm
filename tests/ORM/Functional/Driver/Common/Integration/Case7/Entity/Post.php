<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity;

class Post
{
    public ?int $id = null;
    public string $title = '';
    public string $content = '';

    /** @var iterable<PostTag> */
    public iterable $postTags = [];

    public function __construct(string $title = '', string $content = '')
    {
        $this->title = $title;
        $this->content = $content;
    }
}
