<?php

declare(strict_types=1);

namespace PhpAstInspector\PhpParser;

use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\Parser\RichParser;

final class Parser
{
    /**
     * @return array<Node>
     */
    public function parse(string $code): array
    {
        $currentWorkingDirectory = getcwd();
        assert(is_string($currentWorkingDirectory));
        $containerFactory = new ContainerFactory($currentWorkingDirectory);
        $container = $containerFactory->create(sys_get_temp_dir(), [__DIR__ . '/config.neon'], []);

        /** @var RichParser $parser */
        $parser = $container->getService('currentPhpVersionRichParser');

        $nodes = $parser->parseString($code);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeConnectingVisitor());
        $traverser->traverse($nodes);

        return $nodes;
    }

    public static function createLexer(): Emulative
    {
        return new Emulative([
            'usedAttributes' => ['startFilePos', 'endFilePos'],
        ]);
    }
}
