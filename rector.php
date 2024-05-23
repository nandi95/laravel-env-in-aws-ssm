<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        strictBooleans: true,
        instanceOf: true,
        earlyReturn: true
    )
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        DeclareStrictTypesRector::class
    ]);
