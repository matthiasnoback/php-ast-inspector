<?php

declare(strict_types=1);

namespace PhpAstInspector\Console;

use PhpAstInspector\PhpParser\GetNodeInfo;
use PhpAstInspector\PhpParser\NodeNavigator;
use PhpAstInspector\PhpParser\Parser;
use PhpParser\Node;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class InspectCommand extends Command
{
    public const COMMAND_NAME = 'inspect';

    private readonly CodeFormatter $codeFormatter;

    private readonly Parser $parser;

    private readonly RenderNodeInfo $renderNodeInfo;

    public function __construct()
    {
        parent::__construct();

        $this->codeFormatter = new CodeFormatter();
        $this->parser = new Parser();
        $this->renderNodeInfo = new RenderNodeInfo(new GetNodeInfo());
    }

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->addArgument('file', InputArgument::REQUIRED, 'The PHP script that should be parsed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        assert($output instanceof ConsoleOutputInterface);

        $questionHelper = $this->getHelper('question');
        assert($questionHelper instanceof QuestionHelper);
        $navigateToNode = new NavigateToNode($input);

        $this->registerStyles($output);

        $codeSection = $output->section();
        $infoSection = $output->section();
        $questionSection = $output->section();

        $fileArgument = $input->getArgument('file');
        assert(is_string($fileArgument));
        $code = file_get_contents($fileArgument);
        if (! is_string($code)) {
            throw new RuntimeException('Could not read file: ' . $fileArgument);
        }

        $nodes = $this->parser->parse($code);
        $navigator = NodeNavigator::selectFirstFrom($nodes);

        while (true) {
            $this->printCodeWithHighlightedNode($code, $navigator->currentNode(), $codeSection, $infoSection);

            $navigator = $navigateToNode->basedOnUserInput($navigator, $questionSection);
        }
    }

    private function printCodeWithHighlightedNode(
        string $code,
        Node $node,
        ConsoleSectionOutput $codeSection,
        ConsoleSectionOutput $infoSection
    ): void {
        $codeSection->overwrite(
            $this->codeFormatter->format($code, Highlight::createForPhpParserNode($node)) . "\n"
        );

        $infoSection->overwrite($this->renderNodeInfo->forNode($node));
    }

    private function registerStyles(OutputInterface $output): void
    {
        $output->getFormatter()
            ->setStyle(CodeFormatter::HIGHLIGHT_TAG, new OutputFormatterStyle('yellow', '', ['bold']));
        $output->getFormatter()
            ->setStyle(CodeFormatter::LINE_NUMBER_TAG, new OutputFormatterStyle('gray', '', []));
        $output->getFormatter()
            ->setStyle(NavigateToNode::CHOICE_TAG, new OutputFormatterStyle('', '', ['bold']));
        $output->getFormatter()
            ->setStyle(RenderNodeInfo::SUBNODE_TAG, new OutputFormatterStyle('yellow', '', []));
        $output->getFormatter()
            ->setStyle(RenderNodeInfo::CURRENT_NODE_TAG, new OutputFormatterStyle('green', '', []));
    }
}
