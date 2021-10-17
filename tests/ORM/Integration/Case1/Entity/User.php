<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Integration\Case1\Entity;

use DateTimeImmutable;

class User
{
    private ?int $id = null;
    private string $login;
    private string $passwordHash;
    private DateTimeImmutable $created_at;
    private DateTimeImmutable $updated_at;

    /**
     * @var Post[]
     */
    private array $posts = [];

    /**
     * @var Comment[]
     */
    private array $comments = [];

    public function __construct(string $login, string $password)
    {
        $this->login = $login;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
        $this->setPassword($password);
    }

    public function getId(): ?string
    {
        return $this->id === null ? null : (string)$this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function validatePassword(string $password): bool
    {
        // don't use this test code in your project
        return md5($password) === $this->passwordHash;
    }

    public function setPassword(string $password): void
    {
        // don't use this test code in your project
        $this->passwordHash = md5($password);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
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
}
