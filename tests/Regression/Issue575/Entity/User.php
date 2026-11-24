<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue575\Entity;

class User
{
    public const ROLE = 'user';

    public ?int $id = null;
    public string $login;

    /** @var list<Comment> */
    public array $comments = [];

    public function __construct(string $login)
    {
        $this->login = $login;
    }
}
