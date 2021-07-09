<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\CyclicRef2;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Tenant
{
    public $id;
    public $name;

    /** @var Preference[]|Collection */
    public $preferences;

    /** @var Preference */
    public $preference;

    public $created_at;
    public $updated_at;

    public function __construct()
    {
        $this->preferences = new ArrayCollection();
    }

    public function setPreference(Preference $preference): void
    {
        $this->preference = $preference;
        $this->preferences->add($preference);
    }
}
