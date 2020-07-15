<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\CyclicRef;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Post
{
    public $id;
    public $title;
    public $content;

    /** @var Comment */
    public $lastComment;

    /** @var Comment[]|Collection */
    public $comments;

    public $created_at;
    public $updated_at;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function addComment(Comment $c): void
    {
        $this->lastComment = $c;
        $this->comments->add($c);
    }
}
