<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue427\Entity;

use Ramsey\Uuid\UuidInterface;

class BuyerPartner
{
    public int $buyer_id;
    public UuidInterface $partner_id;
}
