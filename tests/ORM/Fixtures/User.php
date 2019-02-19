<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

class User implements ImagedInterface
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

    /** @var \DateTimeInterface */
    public $time_created;

    /** @var bool */
    public $active;

    /** @var Uuid */
    public $uuid;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->tags = new PivotedCollection();
        $this->favorites = new PivotedCollection();
    }

    public function getID()
    {
        return $this->id;
    }

    public function addComment(Comment $c)
    {
        $this->lastComment = $c;
        $this->comments->add($c);
    }
}