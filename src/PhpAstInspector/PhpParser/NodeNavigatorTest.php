<?php

declare(strict_types=1);

namespace PhpAstInspector\PhpParser;

use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\TestCase;

final class NodeNavigatorTest extends TestCase
{
    public function testNextNodeExists(): void
    {
        $navigator = $this->createNavigator(
            <<<'CODE_SAMPLE'
<?php

function main() {
}

class Test {
}
CODE_SAMPLE
        );

        self::assertTrue($navigator->hasNextNode());
        $navigator = $navigator->navigateToNextNode();
        self::assertInstanceOf(Class_::class, $navigator->currentNode());

        self::assertFalse($navigator->hasNextNode());
    }

    public function testPreviousNodeExists(): void
    {
        $navigator = $this->createNavigator(
            <<<'CODE_SAMPLE'
<?php

function main() {
}

class Test {
}
CODE_SAMPLE
        );

        self::assertFalse($navigator->hasPreviousNode());

        $navigator = $navigator->navigateToNextNode();

        self::assertTrue($navigator->hasPreviousNode());
        $navigator = $navigator->navigateToPreviousNode();
        self::assertInstanceOf(Function_::class, $navigator->currentNode());
    }

    public function testSubnodeExists(): void
    {
        $navigator = $this->createNavigator(<<<'CODE_SAMPLE'
<?php

function main() {
}
CODE_SAMPLE
        );

        self::assertTrue($navigator->hasSubnode());

        $navigator = $navigator->navigateToFirstSubnode();
        self::assertInstanceOf(Identifier::class, $navigator->currentNode());

        self::assertFalse($navigator->hasSubnode());
    }

    public function testParentNodeExists(): void
    {
        $navigator = $this->createNavigator(<<<'CODE_SAMPLE'
<?php

function main() {
}
CODE_SAMPLE
        );

        $navigator = $navigator->navigateToFirstSubnode();

        self::assertTrue($navigator->hasParentNode());
        $navigator = $navigator->navigateToParentNode();
        self::assertInstanceOf(Function_::class, $navigator->currentNode());

        self::assertFalse($navigator->hasParentNode());
    }

    public function testBreadcrumbs(): void
    {
        $navigator = $this->createNavigator(<<<'CODE_SAMPLE'
<?php

function main() {
}
CODE_SAMPLE
        );

        self::assertSame(['PhpParser\Node\Stmt\Function_'], $navigator->breadcrumbs());

        $navigator = $navigator->navigateToFirstSubnode();
        self::assertSame([Function_::class, Identifier::class], $navigator->breadcrumbs());
    }

    private function createNavigator(string $code): NodeNavigator
    {
        $parser = new Parser();
        $nodes = $parser->parse($code);

        return NodeNavigator::selectFirstFrom($nodes);
    }
}
