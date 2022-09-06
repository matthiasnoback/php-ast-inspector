<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class IntegrationTest extends TestCase
{
    public function testItShowsCodeInfoAndQuestion(): void
    {
        $process = new Process(['php', './composer-bin/ast-inspect', 'inspect', 'docs/example.php']);
        $process->start();
        $process->waitUntil(fn () => str_contains($process->getOutput(), 'Next?'));

        // Code section
        self::assertStringContainsString('Hello, world!', $process->getOutput(), $process->getErrorOutput());

        // Info section
        self::assertStringContainsString(
            'PhpParser\Node\Stmt\Declare_',
            $process->getOutput(),
            $process->getErrorOutput()
        );

        // Question section
        self::assertStringContainsString('Next?', $process->getOutput(), $process->getErrorOutput());
    }
}
