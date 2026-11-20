<?php

declare(strict_types=1);

use Cycle\Testing\Config;
use Cycle\Testing\ORM\OrmPlugin;

return Config::forPackage('cycle/orm', root: __DIR__)
    ->unit('tests/ORM/Unit')
    ->feature('tests/ORM/Feature', plugins: [new OrmPlugin()])
    ->regression('tests/ORM/Regression', plugins: [new OrmPlugin()])
    ->build();
