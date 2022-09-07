<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $config): void {
    $config->paths([__DIR__ . '/src', __DIR__ . '/utils', __DIR__ . '/rector.php', __DIR__ . '/ecs.php']);
    $config->skip([PhpUnitStrictFixer::class]);

    $config->sets([SetList::CONTROL_STRUCTURES, SetList::PSR_12, SetList::COMMON, SetList::SYMPLIFY]);
};
