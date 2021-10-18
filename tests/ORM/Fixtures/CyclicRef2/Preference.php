<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\CyclicRef2;

class Preference
{
    public $tenant_id;
    public $id;

    public $flag;
    public $option;

    public $created_at;
    public $updated_at;
}
