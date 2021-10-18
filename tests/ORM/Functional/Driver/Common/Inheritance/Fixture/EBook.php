<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture;

class EBook extends Book
{
    public string $url;
    public ?int $block_id = null;
    /** @var Page[] */
    public array $pages = [];
}
