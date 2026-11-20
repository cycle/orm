<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class TagContextTyped
{
    public $as;
    public ?int $id = null;
    public ?int $user_id = null;
    public ?int $tag_id = null;

    /** @var Image */
    public $image;
}
