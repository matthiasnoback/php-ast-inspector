<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

use PhpParser\Node;

final class Highlight
{
    public function __construct(
        public int $startPosition,
        public int $endPosition,
    ) {
    }

    public static function createForPhpParserNode(Node $node): ?self
    {
        if ($node->getStartFilePos() > -1 && $node->getEndFilePos() > -1) {
            return new self($node->getStartFilePos(), $node->getEndFilePos() + 1);
        }

        return null;
    }
}
