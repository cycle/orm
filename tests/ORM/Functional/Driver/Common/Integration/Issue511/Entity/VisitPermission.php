<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue511\Entity;

class VisitPermission
{
    public int $user_id;
    public User $user;
    public bool $allCities = false;
    public array $cities = [];

    public function __construct(User $user, bool $allCities = false)
    {
        $this->user = $user;
        $this->allCities = $allCities;
    }
}
