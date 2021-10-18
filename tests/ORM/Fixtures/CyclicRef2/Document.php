<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\CyclicRef2;

class Document
{
    public $tenant_id;
    public $id;

    public $tenant;

    public $preference_id;
    public $preference;

    public $body;

    public $created_at;
    public $updated_at;
}
