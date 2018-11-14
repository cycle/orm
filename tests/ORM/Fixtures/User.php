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

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function addComment(Comment $c)
    {
        $this->lastComment = $c;
        $this->comments->add($c);
    }
}