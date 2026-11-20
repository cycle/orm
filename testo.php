<?php

declare(strict_types=1);

use Cycle\Testing\Config;
use Cycle\Testing\ORM\OrmPlugin;

return Config::forPackage('cycle/orm', root: __DIR__)
    ->unit('tests/Unit')
    ->feature('tests/Feature', plugins: [new OrmPlugin()])
    ->regression('tests/Regression', plugins: [new OrmPlugin()])
    ->build();
