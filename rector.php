<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php81: true)
    ->withTypeCoverageLevel(50)
    ->withDeadCodeLevel(23)
    ->withCodeQualityLevel(73)
    ->withRules([
        ExplicitNullableParamTypeRector::class
    ]);
