<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

return \Spiral\CodeStyle\Builder::create()
    ->include(__DIR__ . '/../../src')
    ->include(__DIR__ . '/../../tests')
    ->include(__FILE__)
    ->cache('./runtime/php-cs-fixer.cache')
    ->allowRisky(false)
    ->build();
