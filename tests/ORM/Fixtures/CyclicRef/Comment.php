<?php
// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\CyclicRef;

class Comment
{
    public $id;
    public $post_id;

    public $message;

    /** @var User */
    public $user;

    public $created_at;
    public $updated_at;
}
