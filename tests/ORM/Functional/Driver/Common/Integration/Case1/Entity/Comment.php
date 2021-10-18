<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\Entity;

use DateTimeImmutable;

class Comment
{
    private ?int $id = null;
    private bool $public = false;
    private string $content;
    private DateTimeImmutable $created_at;
    private DateTimeImmutable $updated_at;
    private ?DateTimeImmutable $published_at = null;
    private ?DateTimeImmutable $deleted_at = null;
    // todo private User $user;
    public $user;
    private ?int $user_id = null;
    /**
     * @var Post
     */
    private $post = null;
    private ?int $post_id = null;

    public function __construct(string $content)
    {
        $this->content = $content;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deleted_at;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setPost(Post $post): void
    {
        $this->post = $post;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->published_at;
    }

    public function setPublishedAt(?DateTimeImmutable $published_at): void
    {
        $this->published_at = $published_at;
    }
}
