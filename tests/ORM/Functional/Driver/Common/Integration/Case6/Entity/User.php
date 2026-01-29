<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case6\Entity;

class User
{
    private ?int $id = null;
    private string $login;

    public function __construct(string $login)
    {
        $this->login = $login;
    }

    public function getId(): ?string
    {
        return $this->id === null ? null : (string) $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }
}
