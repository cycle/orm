<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case5\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Buyer extends User
{
    public Collection $partners;

    public function __construct(
        int $id,
        string $name,
        public string $address,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->partners = new ArrayCollection();
    }
}
