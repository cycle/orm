<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case565\Entity;

class User
{
    public const ROLE = 'user';

    public ?int $id = null;
    public string $email = '';
    public float $balance = 0.0;
    public ?UserCredentials $credentials = null;
    public ?UserProfile $profile = null;

    /** @var iterable<Comment> */
    public iterable $comments = [];
}
