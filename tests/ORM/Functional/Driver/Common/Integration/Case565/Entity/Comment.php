<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565\Entity;

class Comment
{
    public ?int $id = null;
    public ?int $user_id = null;
    public string $message = '';
}
