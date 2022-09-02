<?php

declare(strict_types=1);

namespace PhpAstInspector\PhpParser;

use PhpParser\Node;
use RuntimeException;

final class NodeNavigator
{
    public function __construct(
        private readonly Node $currentNode
    ) {
    }

    /**
     * @param array<Node> $nodes
     */
    public static function selectFirstFrom(array $nodes): self
    {
        if ($nodes === []) {
            throw new RuntimeException('Parser did not return any nodes');
        }

        return new self($nodes[0]);
    }

    public function hasNextNode(): bool
    {
        return $this->currentNode->getAttribute('next') instanceof Node;
    }

    public function navigateToNextNode(): self
    {
        $nextNode = $this->currentNode->getAttribute('next');
        assert($nextNode instanceof Node);

        return new self($nextNode);
    }

    public function hasPreviousNode(): bool
    {
        return $this->currentNode->getAttribute('previous') instanceof Node;
    }

    public function navigateToPreviousNode(): self
    {
        $previousNode = $this->currentNode->getAttribute('previous');
        assert($previousNode instanceof Node);

        return new self($previousNode);
    }

    public function currentNode(): Node
    {
        return $this->currentNode;
    }

    public function hasSubnode(): bool
    {
        $subnodes = self::collectSubnodes($this->currentNode);

        return $subnodes !== [];
    }

    public function navigateToFirstSubnode(): self
    {
        $subnodes = self::collectSubnodes($this->currentNode);

        if ($subnodes === []) {
            throw new \RuntimeException('No subnodes found');
        }

        return new self($subnodes[0]);
    }

    public function hasParentNode(): bool
    {
        return $this->currentNode->getAttribute('parent') instanceof Node;
    }

    public function navigateToParentNode(): self
    {
        $parentNode = $this->currentNode->getAttribute('parent');
        assert($parentNode instanceof Node);

        return new self($parentNode);
    }

    /**
     * @return array<string>
     */
    public function breadcrumbs(): array
    {
        $breadcrumbs = [$this->currentNode::class];

        if ($this->hasParentNode()) {
            $navigator = $this->navigateToParentNode();
            array_unshift($breadcrumbs, $navigator->currentNode::class);
        }

        return $breadcrumbs;
    }

    /**
     * @return array<Node>
     */
    private static function collectSubnodes(Node $node): array
    {
        $subnodes = [];
        foreach ($node->getSubNodeNames() as $key) {
            $subnode = $node->{$key};
            if ($subnode instanceof Node) {
                $subnodes[] = $subnode;
            } elseif (is_array($subnode)) {
                foreach ($subnode as $actualSubnode) {
                    if ($actualSubnode instanceof Node) {
                        $subnodes[] = $actualSubnode;
                    }
                }
            }
        }

        return $subnodes;
    }
}
