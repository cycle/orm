<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Inheritance\Fixture;

use Cycle\ORM\Reference\ReferenceInterface;

class Page
{
    public ?int $id = null;
    public ?int $book_id = null;
    public ?int $owner_id = null;
    public ?int $block_id = null;
    public string $title;
    public null|EBook|ReferenceInterface $ebook = null;
    public null|Employee|ReferenceInterface $owner = null;
    public string $content;
}
