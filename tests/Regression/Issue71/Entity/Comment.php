<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue71\Entity;

class Comment
{
    public ?int $id = null;
    public ?int $post_id = null;
    public string $message;
}
