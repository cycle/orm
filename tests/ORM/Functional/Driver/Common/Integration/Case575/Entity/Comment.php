<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case575\Entity;

class Comment
{
    public const ROLE = 'comment';

    public ?int $id = null;
    public string $content;
    public ?User $user = null;
    public ?int $user_id = null;

    public function __construct(string $content)
    {
        $this->content = $content;
    }
}
