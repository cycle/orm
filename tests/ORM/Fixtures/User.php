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
use Spiral\ORM\Util\Collection\PivotedCollection;

class User
{
    public $id;
    public $email;
    public $balance;

    /** @var Profile */
    public $profile;

    /**
     * @invisible
     * @var Comment
     */
    public $lastComment;

    /**
     * @invisible
     * @var Comment[]|Collection
     */
    public $comments;

    /** @var Tag[]|Collection */
    public $tags;

    /**
     * @invisible
     * @var Comment[]|Collection
     */
    public $favorites;

    /** @var Nested */
    public $nested;

    /** @var Nested */
    public $owned;

    /** @var Image */
    public $image;

    /** @var Post[]|Collection */
    public $posts;

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