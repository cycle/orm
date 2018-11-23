<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Spiral\ORM\Collection\PivotedCollection;

class User
{
    public $id;
    public $email;
    public $balance;

    /** @var Profile */
    public $profile;

    /** @var Comment */
    public $lastComment;

    /** @var Comment[]|Collection */
    public $comments;

    /** @var Tag[]|Collection */
    public $tags;

    /** @var Comment[]|Collection */
    public $favorites;

    /** @var Nested */
    public $nested;

    /** @var Nested */
    public $owned;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->tags = new PivotedCollection();
        $this->favorites = new PivotedCollection();
    }

    public function addComment(Comment $c)
    {
        $this->lastComment = $c;
        $this->comments->add($c);
    }
}