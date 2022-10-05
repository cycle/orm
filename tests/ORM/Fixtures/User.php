<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class User implements ImagedInterface
{
    use ProtectedFieldsTrait;

    public $id;
    public $email;
    public $balance;
    public $user_code;

    /** @var Profile */
    public $profile;

    /**
     * @invisible
     *
     * @var Comment
     */
    public $lastComment;

    /**
     * @var Collection|Comment[]
     */
    public $comments;

    /** @var Collection<array-key, Tag>|Tag[] */
    public $tags;

    /**
     * @invisible
     *
     * @var Collection<array-key, Comment>|Comment[]
     */
    public $favorites;

    /** @var Nested */
    public $nested;

    /** @var Nested */
    public $owned;

    /** @var Image */
    public $image;

    /** @var Collection|Post[] */
    public $posts;

    /** @var \DateTimeInterface */
    public $time_created;

    /** @var bool */
    public $active;

    /** @var Uuid */
    public $uuid;

    /**
     * @var UserCredentials
     */
    public $credentials;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->tags = new PivotedCollection();
        $this->favorites = new PivotedCollection();
        $this->credentials = new UserCredentials();
    }

    public function getID()
    {
        return $this->id;
    }

    public function addComment(Comment $c): void
    {
        $this->lastComment = $c;
        $this->comments->add($c);
    }
}
