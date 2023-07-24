<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue425\Entity;

class Comment
{
    public int $id;
    public string $content;
    public Post $post;
    public int $post_id;
}
