<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

trait ProtectedFieldsTrait
{
    protected mixed $protected;
    protected mixed $protectedRelation;
    private mixed $private;
    private mixed $privateRelation;

    public function getProtected(): mixed
    {
        return $this->protected;
    }

    public function getPrivate(): mixed
    {
        return $this->private;
    }

    public function getProtectedRelation(): mixed
    {
        return $this->protectedRelation;
    }

    public function getPrivateRelation(): mixed
    {
        return $this->privateRelation;
    }

    public function setPrivateRelation(mixed $data): void
    {
        $this->privateRelation = $data;
    }

    public function setProtectedRelation(mixed $data): void
    {
        $this->protectedRelation = $data;
    }
}
