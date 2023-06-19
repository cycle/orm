<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class Account
{
    public const ROLE = 'account';

    public const F_UUID = 'uuid';
    public const F_EMAIL = 'email';
    public const F_PASSWORD_HASH = 'passwordHash';
    public const F_UPDATED_AT = 'updatedAt';

    /** @readonly */
    public DateTimeInterface $updatedAt;

    public Identity $identity;

    public function __construct(
        /** @readonly */
        public UuidInterface $uuid,
        public string $email,
        public string $passwordHash,
    ) {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
