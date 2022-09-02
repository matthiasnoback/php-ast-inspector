<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (
    ContainerConfigurator $containerConfigurator
): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(
        Option::PATHS,
        [
            __DIR__ . '/src',
        ]
    );
    $parameters->set(
        Option::SKIP,
        [
            PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer::class
        ]
    );

    $containerConfigurator->import(SetList::CONTROL_STRUCTURES);
    $containerConfigurator->import(SetList::PSR_12);
    $containerConfigurator->import(SetList::COMMON);
    $containerConfigurator->import(SetList::SYMPLIFY);
};
