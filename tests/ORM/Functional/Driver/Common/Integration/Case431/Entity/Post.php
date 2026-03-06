<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case431\Entity;

class Post
{
    public $id = null;
    public string $slug;
    public string $title = '';
    public bool $public = false;
    public string $content = '';
    public \DateTimeImmutable $created_at;
    public \DateTimeImmutable $updated_at;
    public ?\DateTimeImmutable $published_at = null;
    public ?\DateTimeImmutable $deleted_at = null;
    public User $user;
    public $user_id = null;

    /** @var iterable<Comment> */
    public iterable $comments = [];

    public function __construct(string $title = '', string $content = '')
    {
        $this->title = $title;
        $this->content = $content;
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
        $this->slug = \bin2hex(\random_bytes(32));
    }
}
