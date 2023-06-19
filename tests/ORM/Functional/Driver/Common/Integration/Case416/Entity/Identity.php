<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class Identity
{
    public const ROLE = 'identity';

    public const F_UUID = 'uuid';
    public const F_CREATED_AT = 'createdAt';
    public const F_UPDATED_AT = 'updatedAt';
    public const F_DELETED_AT = 'deletedAt';

    /** @readonly */
    public DateTimeInterface $createdAt;
    /** @readonly */
    public DateTimeInterface $updatedAt;
    /** @readonly */
    public ?DateTimeInterface $deletedAt = null;

    public Profile $profile;

    public Account $account;

    public function __construct(
        /** @readonly */
        public UuidInterface $uuid,
    ) {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }
}
