<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\Entity;

class Translation
{
    public ?int $id = null;
    public Country $country;
    public Locale $locale;
    public string $title;

    public function __construct(Country $country, Locale $locale, string $title)
    {
        $this->country = $country;
        $this->locale = $locale;
        $this->title = $title;
    }
}
