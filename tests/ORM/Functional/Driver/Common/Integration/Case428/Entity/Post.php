<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\Entity;

class Post
{
    public ?int $id = null;
    public string $title = '';
    public string $content = '';
    public \DateTimeImmutable $created_at;
    public \DateTimeImmutable $updated_at;
    public ?Comment $best_comment = null;
    public ?int $best_comment_id = null;
    public ?User $user = null;
    public ?int $user_id = null;
    public ?Category $category = null;
    public ?int $category_id = null;
    public Metadata $metadata;

    public function __construct(
        string $title = '',
        string $content = '',
        string $metadata = '',
    ) {
        $this->title = $title;
        $this->content = $content;
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
        $this->metadata = new Metadata($metadata);
    }
}
