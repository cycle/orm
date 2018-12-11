<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Fixtures;


use Spiral\Cycle\Relation\Pivoted\PivotedCollection;
use Spiral\Cycle\Relation\Pivoted\PivotedCollectionInterface;

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