<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Collection\Pivoted\PivotedCollectionInterface;

class Comment
{
    public $id;

    public $message;

    /** @var User */
    public $user;

    /** @var PivotedCollectionInterface|User[] */
    public $favoredBy;

    public $parent;

    public $level;

    public function __construct()
    {
        $this->favoredBy = new PivotedCollection();
    }
}
