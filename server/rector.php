<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/cache', __DIR__ . '/controller', __DIR__ . '/device', __DIR__ . '/entity', __DIR__ . '/middleware', __DIR__ . '/runner', __DIR__ . '/service', __DIR__ . '/task'])
    ->withCache(__DIR__ . '/var/cache/rector')
    ->withParallel()
    ->withPreparedSets(codeQuality: true, codingStyle: true, typeDeclarations: true, privatization: true, instanceOf: true, earlyReturn: true, strictBooleans: true)
    ->withImportNames(removeUnusedImports: true)
    ->withPhpVersion(PhpVersion::PHP_82);