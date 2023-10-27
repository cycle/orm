<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class Pivot implements ImagedInterface
{
    public $id;
    public $parent_id;
    public $child_id;
}
