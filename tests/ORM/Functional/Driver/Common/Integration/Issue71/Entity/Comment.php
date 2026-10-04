<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue71\Entity;

class Comment
{
    public ?int $id = null;
    public ?int $post_id = null;
    public string $message;
}
