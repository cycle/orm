<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class Profile
{
    public const ROLE = 'profile';

    public const F_UUID = 'uuid';
    public const F_UPDATED_AT = 'updatedAt';

    /** @readonly */
    public DateTimeInterface $updatedAt;

    /** @psalm-suppress InvalidArgument */
    public UserName $name;

    public Identity $identity;

    public function __construct(
        /** @readonly */
        public UuidInterface $uuid,
    ) {
        $this->name = new UserName();
        $this->updatedAt = new \DateTimeImmutable();
    }
}
