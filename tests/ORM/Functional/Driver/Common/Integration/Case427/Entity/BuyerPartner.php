<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case427\Entity;

use Ramsey\Uuid\UuidInterface;

class BuyerPartner
{
    public int $buyer_id;
    public UuidInterface $partner_id;
}
