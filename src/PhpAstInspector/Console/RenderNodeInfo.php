<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

use PhpAstInspector\PhpParser\GetNodeInfo;
use PhpAstInspector\PhpParser\NodeNavigator;
use PhpParser\Node;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

final class RenderNodeInfo
{
    public function __construct(
        private readonly GetNodeInfo $getNodeInfo
    ) {
    }

    public function forNode(Node $node): string
    {
        $tempOutput = new BufferedOutput();
        $breadcrumbs = (new NodeNavigator($node))->breadcrumbs();
        $breadcrumbs[count($breadcrumbs) - 1] = '<current_node>' . $breadcrumbs[count(
            $breadcrumbs
        ) - 1] . '</current_node>';
        $tempOutput->writeln('Current node: ' . implode(' > ', $breadcrumbs) . "\n");

        $table = new Table($tempOutput);
        $table->setStyle('compact');
        $nodeInfo = $this->getNodeInfo->forNode($node);
        foreach ($nodeInfo as $key => $value) {
            $table->addRow(['<subnode>' . $key . '</subnode>', $value]);
        }
        $table->render();

        return $tempOutput->fetch();
    }
}
