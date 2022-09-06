<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\GetHelperRule\Fixtures;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CallsGetHelperInExecute extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
    }
}
