<?php

declare(strict_types=1);

namespace PhpAstInspector\PhpParser;

use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PhpParser\ParserFactory;
use RuntimeException;

final class Parser
{
    /**
     * @return array<Node>
     */
    public function parse(string $code): array
    {
        $parser = (new ParserFactory())->create(
            ParserFactory::PREFER_PHP7,
            new Emulative([
                'usedAttributes' => ['startFilePos', 'endFilePos'],
            ])
        );

        $nodes = $parser->parse($code);
        if ($nodes === null) {
            throw new RuntimeException('Parser did not return any nodes');
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeConnectingVisitor());
        $traverser->traverse($nodes);

        return $nodes;
    }
}
