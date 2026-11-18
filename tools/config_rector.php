<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php73\Rector\ConstFetch\SensitiveConstantNameRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Removing\Rector\FuncCall\RemoveFuncCallRector;
use Rector\Set\ValueObject\LevelSetList;

$root = dirname(__DIR__);

return RectorConfig::configure()
    ->withPaths([
        $root . '/index.php',
        $root . '/app',
        $root . '/lib',
        $root . '/modules',
        $root . '/public',
    ])
    ->withFileExtensions(['php', 'inc'])
    ->withSkip([
        $root . '/lib/Classes',
        $root . '/lib/legacy-packages',
        $root . '/lib/JSPacker',
        $root . '/third-party',
        $root . '/third-party/Classes',
        SensitiveConstantNameRector::class,
        ClosureToArrowFunctionRector::class,
        ChangeSwitchToMatchRector::class,
        RemoveFuncCallRector::class,
    ])
    ->withPhpSets(php85: true)
    ->withPreparedSets(deadCode: false, codeQuality: false);