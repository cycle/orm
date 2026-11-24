<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Case7\Entity;

class PostTag
{
    public ?int $id = null;
    public ?int $post_id = null;
    public ?int $tag_id = null;
    public ?Post $post = null;
    public ?Tag $tag = null;
}
