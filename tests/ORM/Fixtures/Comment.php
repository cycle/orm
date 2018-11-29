<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures;

use Spiral\ORM\Util\Collection\PivotedCollection;
use Spiral\ORM\Util\Collection\PivotedInterface;

class Comment
{
    public $id;

    public $message;

    /** @var User */
    public $user;

    /** @var User[]|PivotedInterface */
    public $favorited_by;

    public $parent;

    public function __construct()
    {
        $this->favorited_by = new PivotedCollection();
    }
}