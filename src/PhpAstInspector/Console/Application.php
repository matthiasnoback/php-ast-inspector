<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

use Symfony\Component\Console\Application as SymfonyConsoleApplication;

final class Application extends SymfonyConsoleApplication
{
    public function __construct()
    {
        parent::__construct('PHP AST Inspector', '0.1');

        $this->addCommands([new InspectCommand()]);
    }
}
