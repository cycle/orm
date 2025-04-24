<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity;

class PostTag
{
    public ?int $id = null;
    public ?int $post_id = null;
    public ?int $tag_id = null;

    /** @var ?Post */
    public ?Post $post = null;

    /** @var ?Tag */
    public ?Tag $tag = null;
}
