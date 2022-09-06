<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\GetHelperRule\Fixtures;

class CallsGetHelperInClassThatIsNotACommand
{
    public function __construct()
    {
        $questionHelper = $this->getHelper('question');
    }
}
