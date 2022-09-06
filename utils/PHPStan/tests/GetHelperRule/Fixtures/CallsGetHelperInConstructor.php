<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\GetHelperRule\Fixtures;

use Symfony\Component\Console\Command\Command;

class CallsGetHelperInConstructor extends Command
{
    public function __construct()
    {
        $questionHelper = $this->getHelper('question');
    }
}
