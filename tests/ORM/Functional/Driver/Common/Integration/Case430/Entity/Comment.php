<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case430\Entity;

use Ramsey\Uuid\UuidInterface;

class Comment
{
    public UuidInterface $uuid;
    public bool $public = false;
    public string $content;
    public \DateTimeImmutable $created_at;
    public \DateTimeImmutable $updated_at;
    public ?\DateTimeImmutable $published_at = null;
    public ?\DateTimeImmutable $deleted_at = null;
    public User $user;
    public ?UuidInterface $user_uuid = null;
    public ?Post $post = null;
    public ?UuidInterface $post_uuid = null;
}
