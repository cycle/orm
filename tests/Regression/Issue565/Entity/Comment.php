<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue565\Entity;

class Comment
{
    public ?int $id = null;
    public ?int $user_id = null;
    public string $message = '';
}
