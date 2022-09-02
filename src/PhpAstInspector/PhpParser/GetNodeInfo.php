<?php

declare(strict_types=1);

namespace PhpAstInspector\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeDumper;

final class GetNodeInfo extends NodeDumper
{
    /**
     * @return array<string,string>
     */
    public function forNode(Node $node): array
    {
        $info = [];

        foreach ($node->getSubNodeNames() as $key) {
            assert(is_string($key));

            $info[$key] = $this->dumpValue($node, $key, $node->{$key});
        }

        return $info;
    }

    protected function dumpValue(Node $node, string $key, mixed $value): string
    {
        if ($value === null) {
            return 'null';
        } elseif ($value === false) {
            return 'false';
        } elseif ($value === true) {
            return 'true';
        } elseif (is_object($value)) {
            return $value::class;
        } elseif (is_array($value)) {
            return '[' . implode(
                ', ',
                array_map(fn (mixed $element) => is_object($element) ? $element::class : '...', $value)
            ) . ']';
        } elseif (is_scalar($value)) {
            if ($key === 'flags' || $key === 'newModifier') {
                return (string) $this->dumpFlags($value);
            } elseif ($key === 'type' && $node instanceof Include_) {
                return (string) $this->dumpIncludeType($value);
            } elseif ($key === 'type'
                && ($node instanceof Use_ || $node instanceof UseUse || $node instanceof GroupUse)) {
                return (string) $this->dumpUseType($value);
            }
            return (string) $value;
        }

        return '...';
    }
}
