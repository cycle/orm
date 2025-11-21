<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity;

class Comment
{
    public ?int $id = null;
    public string $content;
    public \DateTimeImmutable $created_at;
    public \DateTimeImmutable $updated_at;
    public Post $post;
    public int $post_id;

    public function __construct(string $content, Post $post)
    {
        $this->post = $post;
        $this->content = $content;
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }
}
