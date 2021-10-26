<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\Entity;

use DateTimeImmutable;

class Post
{
    private ?int $id = null;
    private string $slug;
    private string $title = '';
    private bool $public = false;
    private string $content = '';
    private DateTimeImmutable $created_at;
    private DateTimeImmutable $updated_at;
    private ?DateTimeImmutable $published_at = null;
    private ?DateTimeImmutable $deleted_at = null;
    private User $user;
    private ?int $user_id = null;
    /**
     * @var Tag[]
     */
    private array $tags = [];
    private ?int $tag_id = null;
    /**
     * @var Comment[]
     */
    private array $comments = [];

    public function __construct(string $title = '', string $content = '')
    {
        $this->title = $title;
        $this->content = $content;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
        $this->resetSlug();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function resetSlug(): void
    {
        $this->slug = \random_bytes(128);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
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

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->published_at;
    }

    public function setPublishedAt(?DateTimeImmutable $published_at): void
    {
        $this->published_at = $published_at;
    }

    /**
     * @return Comment[]
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    public function addComment(Comment $post): void
    {
        $this->comments[] = $post;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): void
    {
        $this->tags[] = $tag;
    }

    public function resetTags(): void
    {
        $this->tags = [];
    }

    public function isNewRecord(): bool
    {
        return $this->getId() === null;
    }
}