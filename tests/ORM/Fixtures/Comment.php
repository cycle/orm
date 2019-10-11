<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Relation\Pivoted\PivotedCollection;
use Cycle\ORM\Relation\Pivoted\PivotedCollectionInterface;

class Comment
{
    public $id;

    public $message;

    /** @var User */
    public $user;

    /** @var User[]|PivotedCollectionInterface */
    public $favoredBy;

    public $parent;

    public $level;

    public function __construct()
    {
        $this->favoredBy = new PivotedCollection();
    }
}
