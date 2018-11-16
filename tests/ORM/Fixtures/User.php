<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Spiral\ORM\Collection\PivotedCollection;
use Spiral\ORM\Collection\PivotedCollectionInterface;

class User
{
    public $id;
    public $email;
    public $balance;

    /** @var Profile */
    public $profile;

    /** @var Comment */
    public $lastComment;

    /** @var Comment[]|PivotedCollectionInterface */
    public $comments;

    /** @var Tag[]|PivotedCollectionInterface */
    public $tags;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->tags = new PivotedCollection();
    }

    public function addComment(Comment $c)
    {
        $this->lastComment = $c;
        $this->comments->add($c);
    }
}