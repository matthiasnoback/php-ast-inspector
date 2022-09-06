<?php

declare(strict_types=1);

namespace Utils\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\Console\Command\Command;

/**
 * @implements Rule<MethodCall>
 */
final class GetHelperRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Identifier) {
            // This is a dynamic method call
            return [];
        }

        if ($node->name->name !== 'getHelper') {
            // This is not a call to getHelper()
            return [];
        }

        if (! $scope->getFunction() instanceof MethodReflection) {
            // This method call happens outside a method
            return [];
        }

        if (! $scope->getFunction()->getDeclaringClass()->isSubclassOf(Command::class)) {
            // This is not a command class
            return [];
        }

        if ($scope->getFunctionName() !== '__construct') {
            // The call happens outside the constructor
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'getHelper() should not be called in the constructor because helpers have not been registered at that point'
            )->build(),
        ];
    }
}
