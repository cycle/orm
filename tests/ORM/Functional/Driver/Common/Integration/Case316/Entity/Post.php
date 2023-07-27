<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case316\Entity;

use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case316\BinaryId;
use DateTimeImmutable;

class Post
{
    public ?BinaryId $id = null;
    public string $slug;
    public string $title = '';
    public bool $public = false;
    public $content = '';
    public DateTimeImmutable $created_at;
    public DateTimeImmutable $updated_at;
    public ?DateTimeImmutable $published_at = null;
    public ?DateTimeImmutable $deleted_at = null;
    public User $user;
    public ?int $user_id = null;
    /** @var iterable<Tag> */
    public iterable $tags = [];
    public ?int $tag_id = null;
    /** @var iterable<Comment> */
    public iterable $comments = [];

    public function __construct(string $title = '', string $content = '')
    {
        $this->id = BinaryId::create();
        $this->title = $title;
        $this->content = $content;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
        $this->resetSlug();
    }

    public function resetSlug(): void
    {
        $this->slug = \bin2hex(\random_bytes(32));
    }
}
