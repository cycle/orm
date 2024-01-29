<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case398\Entity;

class FilterProduct
{
    public const ROLE = 'filter_product';

    public function __construct(
        public int $productId,
        public int $filterId,
    ) {
    }
}
