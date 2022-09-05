<?php

declare(strict_types=1);

namespace PhpAstInspector\PhpParser;

use PhpParser\Lexer;
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
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, self::createLexer());

        $nodes = $parser->parse($code);

        if ($nodes === null) {
            throw new RuntimeException('Parser returned no nodes');
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeConnectingVisitor());
        $traverser->traverse($nodes);

        return $nodes;
    }

    private static function createLexer(): Lexer
    {
        return new Emulative([
            'usedAttributes' => ['startFilePos', 'endFilePos'],
        ]);
    }
}
