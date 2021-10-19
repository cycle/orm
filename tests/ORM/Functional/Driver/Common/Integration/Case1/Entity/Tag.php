<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\Entity;

use DateTimeImmutable;

class Tag
{
    private ?int $id = null;
    private string $label;
    private DateTimeImmutable $created_at;
    /**
     * @var Post[]
     */
    private array $posts = [];

    public function __construct(string $label)
    {
        $this->label = $label;
        $this->created_at = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     * @return Post[]
     */
    public function getPosts(): array
    {
        return $this->posts;
    }

    public function addPost(Post $post): void
    {
        $this->posts[] = $post;
    }
}
