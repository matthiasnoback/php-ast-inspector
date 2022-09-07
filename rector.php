<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $config): void {
    $config->paths([__DIR__ . '/src', __DIR__ . '/utils', __DIR__ . '/rector.php', __DIR__ . '/ecs.php']);
    $config->importNames();

    $config->sets([LevelSetList::UP_TO_PHP_81]);
};
