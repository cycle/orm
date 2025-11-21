<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity;

class Comment
{
    public \DateTimeImmutable $created_at;
    public \DateTimeImmutable $updated_at;
    public ?int $post_id = null;
    public ?int $user_id = null;
    public ?Comment $parent = null;
    public ?int $parent_id = null;

    public function __construct(
        public int $id,
        public string $content,
        public Post $post,
        public User $user
    ) {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }
}
