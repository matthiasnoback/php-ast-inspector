<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\GetHelperRule;

use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\GetHelperRule;

/**
 * @extends RuleTestCase<GetHelperRule>
 */
final class GetHelperRuleTest extends RuleTestCase
{
    public function testCallToGetHelperInConstructor(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixtures/CallsGetHelperInConstructor.php'],
            [['getHelper() should not be called in the constructor', 13]]
        );
    }

    public function testSkipCallToGetHelperInExecute(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixtures/CallsGetHelperInExecute.php'],
            [] // no errors
        );
    }

    public function testSkipCallToGetHelperInClassThatIsNotACommand(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixtures/CallsGetHelperInClassThatIsNotACommand.php'],
            [] // no errors
        );
    }

    protected function getRule(): GetHelperRule
    {
        return new GetHelperRule();
    }
}
