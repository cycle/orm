<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case316\Entity;

use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case316\BinaryId;
use DateTimeImmutable;

class Comment
{
    public $id = null;
    public bool $public = false;
    public $content;
    public DateTimeImmutable $created_at;
    public DateTimeImmutable $updated_at;
    public ?DateTimeImmutable $published_at = null;
    public ?DateTimeImmutable $deleted_at = null;
    public User $user;
    public ?int $user_id = null;
    public ?Post $post = null;
    public ?BinaryId $post_id = null;

    public function __construct(string $content)
    {
        $this->content = $content;
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }
}
