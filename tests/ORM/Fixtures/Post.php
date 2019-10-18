<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Relation\Pivoted\PivotedCollectionInterface;

class Post implements ImagedInterface
{
    public $title;
    public $content;
    public $image;

    /** @var Comment[]|PivotedCollectionInterface */
    public $comments;
}
